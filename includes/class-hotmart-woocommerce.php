<?php
require_once HOTMART_PLUGIN_INCLUDES_DIR . 'hotmart-functions.php';

/**
 * Classe responsável pela integração com o WooCommerce.
 */
class Hotmart_WooCommerce {

    /**
     * Cria um pedido no WooCommerce para um novo usuário.
     *
     * @param array $order_data Dados do pedido.
     * @param string $first_name Primeiro nome do cliente.
     * @param string $email E-mail do cliente.
     * @param string $product_name Nome do produto comprado.
     * @param string $transaction_id ID da transação na Hotmart.
     * @return WC_Order|WP_Error O objeto do pedido criado ou um objeto WP_Error em caso de erro.
     */
    public function wc_custom_create_order_hotmart($order_data, $first_name, $email, $product_name, $transaction_id) {
        $address = array(
            'first_name' => $first_name,
            'email'      => $email,
        );

        // Verifica se o produto existe
        $product = get_page_by_title($product_name, OBJECT, 'product');
        if (!$product) {
            $error_message = "Product not found: " . $product_name;
            hotmart_log_error($error_message, false, true); // Marca como erro crítico
            return new WP_Error('product_not_found', 'Produto da Hotmart não encontrado no WooCommerce', $error_message);
        }

        // Cria um novo pedido com os dados fornecidos.
        $order = wc_create_order($order_data);
        if (is_wp_error($order)) {
            hotmart_log_error("Erro ao criar pedido durante o webhook: " . $order->get_error_message(), false, true, ['Transaction ID' => $transaction_id, 'User Details' => ['email' => $email, 'first_name' => $first_name]]);
            return $order;
        }

        // Adiciona o produto encontrado ao pedido
    if (!$order->add_product(wc_get_product($product->ID), 1)) {
        $error_message = "Error adding product to order: " . $product_name;
        hotmart_log_error($error_message, false, true);
        return new WP_Error('error_adding_product', $error_message);
    }


        // Define o endereço de cobrança, calcula os totais e atualiza o status para 'completed'.
        $order->set_address($address, 'billing');
        $order->calculate_totals();
        if (!$order->update_status("completed", '[Compra pela hotmart]')) {
            $error_message = "Error updating order status for order ID: " . $order->get_id();
            hotmart_log_error($error_message, false, true);
            return new WP_Error('error_updating_status', $error_message);
        }

        // Armazena o número da transação da hotmart como metadado do pedido
        $order->update_meta_data('hotmart_transaction_id', $transaction_id);
        $order->save();

        return $order;
    }

    /**
     * Processa um pedido no WooCommerce para um usuário existente.
     *
     * @param int $user_id ID do usuário no WordPress.
     * @param string $product_name Nome do produto comprado.
     * @return WC_Order|WP_Error O objeto do pedido processado ou um objeto WP_Error em caso de erro.
     */
    public function wc_custom_process_order_for_existing_user($user_id, $product_name) {
        // Verifica se o produto existe
        $product = get_page_by_title($product_name, OBJECT, 'product');
        if (!$product) {
            $error_message = "Product not found for existing user: " . $product_name;
            hotmart_log_error($error_message, false, true);
            return new WP_Error('product_not_found', $error_message);
        }

        // Cria um novo pedido
        $order = wc_create_order();
        if (is_wp_error($order)) {
            // Aqui você recupera as informações do usuário
            $user_info = get_userdata($user_id);
            $user_details = [
                'User ID' => $user_id,
                'Email' => $user_info->user_email,
                'Name' => $user_info->display_name
                // Outras informações que você achar relevantes
            ];
            hotmart_log_error("Error creating order for existing user: " . $order->get_error_message(), false, true, $user_details);
            return $order;
        }
      
        // Adiciona o produto ao pedido
        if (!$order->add_product(wc_get_product($product->ID), 1)) {
            $error_message = "Error adding product to order for existing user: " . $product_name;
            hotmart_log_error($error_message, false, true);
            return new WP_Error('error_adding_product', $error_message);
        }

        // Define o ID do cliente, calcula os totais e atualiza o status
        $order->set_customer_id($user_id);
        $order->calculate_totals();
        if (!$order->update_status('completed', 'Pedido completado automaticamente para usuário existente.', TRUE)) {
            $error_message = "Error updating order status for existing user: " . $user_id;
            hotmart_log_error($error_message, false, true);
            return new WP_Error('error_updating_status', $error_message);
        }

        return $order;
    }

    /**
     * Processa um reembolso ou chargeback no WooCommerce com base no número da transação da Hotmart.
     *
     * @param string $transaction_id Número da transação da Hotmart.
     */
    public function wc_custom_refund_order_by_transaction_id($transaction_id) {
        // Procura por pedidos que contenham o metadado 'hotmart_transaction_id' igual ao $transaction_id.
        $orders = wc_get_orders(array(
            'meta_key' => 'hotmart_transaction_id',
            'meta_value' => $transaction_id,
            'status' => array('wc-completed', 'wc-processing'),
        ));

        if (empty($orders)) {
            hotmart_log_error("No orders found for transaction ID: " . $transaction_id, false, true); // Marca como erro crítico
            return;
        }

        foreach ($orders as $order) {
            // Processa o reembolso para o pedido encontrado.
            $order->update_status('wc-refunded', 'Pedido reembolsado automaticamente devido a chargeback ou reembolso.');
        }
    }
}
