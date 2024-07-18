<?php
require_once HOTMART_PLUGIN_INCLUDES_DIR . 'hotmart-functions.php';
require_once HOTMART_PLUGIN_INCLUDES_DIR . 'class-hotmart-webhook.php';


/**
 * Classe responsável por lidar com o recebimento e processamento de webhooks da Hotmart.
 */
class Hotmart_Webhook {
    /**
     * Construtor da classe.
     * Registra a ação para criar o endpoint do webhook quando a API REST do WordPress for inicializada.
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_endpoint'));
    }

    /**
     * Registra o endpoint da API REST para receber os webhooks da Hotmart.
     */
    public function register_endpoint() {
        register_rest_route('hotmart-webhook/v1', '/process/', array(
            'methods' => 'POST',
            'callback' => array($this, 'hotmart_webhook_callback'),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * Função de callback que processa os dados recebidos no webhook da Hotmart.
     *
     * @param WP_REST_Request $request Objeto da requisição do webhook.
     * @return WP_REST_Response Resposta da API REST.
     */
    public function hotmart_webhook_callback(WP_REST_Request $request) {
        // Log dos dados brutos recebidos
        $data_raw = $request->get_body();
        hotmart_log_error("Dados brutos recebidos: " . $data_raw, true);

        // Obtém os dados JSON enviados para o webhook.
        $data = $request->get_json_params(); 
        if (!$data) {
            hotmart_log_error('No data provided in request.', false, true, ['Request Body' => $request->get_body()]);
            return new WP_REST_Response(array('message' => 'No data provided'), 400);
        }

    // Obtém o hottok da query string da URL, do corpo ou do cabeçalho
    $hottok_recebido = $request->get_param('hottok');
    if (!$hottok_recebido) {
        $data = $request->get_json_params();
        $hottok_recebido = isset($data['hottok']) ? $data['hottok'] : $request->get_header('hottok');
    }

        // Seu hottok real (substitua pelo seu hottok)
        $hottok_esperado = HOTMART_WEBHOOK_TOKEN; // Obtém o hottok do arquivo de configuração

        // Compara o hottok recebido com o esperado
        if ($hottok_recebido !== $hottok_esperado) {
            hotmart_log_error('Hottok inválido: ' . $hottok_recebido);
            return new WP_REST_Response(array('message' => 'Hottok inválido'), 403); // Retorna erro 403 Forbidden
        }

        // Definindo as variáveis $transactionId e $userDetails
        $transactionId = $data["purchase"]["transaction"];
        $userDetails = $data["buyer"];


        // Verifica se todos os campos necessários estão presentes nos dados.
        $required_keys = ["buyer", "product", "purchase", "event"];
        foreach ($required_keys as $key) {
            if (!isset($data[$key])) {
                hotmart_log_error("Missing data: $key in request.");
                return new WP_REST_Response(array('message' => "Missing data: $key"), 400);
            }
        }

        // Valida o formato dos dados recebidos.
        if (!is_array($data["buyer"]) || !is_array($data["product"]) || !is_array($data["purchase"])) {
            hotmart_log_error('Invalid data format in request.');
            return new WP_REST_Response(array('message' => 'Invalid data format'), 400);
        }

        // Valida e sanitiza o e-mail do cliente.
        $email = sanitize_email($data["buyer"]["email"]);
        if (!is_email($email)) {
            hotmart_log_error('Invalid email address provided: ' . $email);
            return new WP_REST_Response(array('message' => 'Invalid email address'), 400);
        }

        // Sanitiza e valida o nome completo do cliente.
        $full_name = sanitize_text_field($data["buyer"]["name"]);
        if (empty($full_name)) {
            hotmart_log_error('Full name is empty.');
            return new WP_REST_Response(array('message' => 'Full name is empty'), 400);
        }
        list($first_name, $last_name) = hotmart_split_full_name($full_name); // Divide o nome completo em primeiro e último nome.
        $username = str_replace(' ', '', strtolower($full_name)); // Cria um nome de usuário a partir do nome completo, em minúsculas.

        // Verifica se o nome de usuário já existe e ajusta se necessário.
        if (username_exists($username)) {
            $suffix = 1;
            $new_username = $username . $suffix;
            while (username_exists($new_username)) {
                $suffix++;
                $new_username = $username . $suffix;
            }
            $username = $new_username;
        }

        $nickname = $full_name; // Define o apelido do usuário.
        $product_name = sanitize_text_field($data["product"]["name"]); // Sanitiza o nome do produto.
        $token = sanitize_text_field($request->get_header('authorization')); // Obtém o token de autorização do cabeçalho da requisição.

        // Processa a venda com base no status atual.
        $current_status = $data["event"];
        $transaction_id = $data["purchase"]["transaction"];

        if ($current_status == "PURCHASE_PROTEST" || $current_status == "PURCHASE_CHARGEBACK") {
            wc_custom_refund_order_by_transaction_id($transaction_id); // Processa o reembolso com base no número da transação.

          
        } elseif ($current_status == "PURCHASE_APPROVED") {
            $user = get_user_by('email', $email); // Obtém o usuário pelo e-mail.
            if (!$user) {
                // Se o usuário não existir, cria um novo.
                $password = wp_generate_password(); // Gera uma senha.
                $user_id = wp_create_user($username, $password, $email); // Cria o usuário.
                if (is_wp_error($user_id)) {
        hotmart_log_error("Error creating user: " . $user_id->get_error_message());
        return new WP_REST_Response(array('message' => 'Failed to create user'), 500);
    }

                // Atualiza os dados do usuário com informações fornecidas.
                wp_update_user(array('ID' => $user_id, 'first_name' => $first_name, 'last_name' => $last_name, 'nickname' => $nickname, 'display_name' => $full_name));
                $hotmart_emails = new Hotmart_Emails();
                $hotmart_emails->send_welcome_email($email, $first_name, $password); // Envia um e-mail de boas-vindas ao novo usuário.
                $hotmart_woocommerce = new Hotmart_WooCommerce();
                $order = $hotmart_woocommerce->wc_custom_create_order_hotmart(array('status' => 'completed', 'customer_id' => $user_id), $first_name, $email, $product_name, $transaction_id); // Cria um pedido para o novo usuário.
    if (is_wp_error($order)) {
        hotmart_log_error("Erro ao criar pedido durante o webhook: " . $order->get_error_message(), false, true);
        return new WP_REST_Response(array('message' => 'Failed to create order'), 500);
    }

            } else {
                // Se o usuário já existir, processa o pedido para o usuário existente.
                $hotmart_woocommerce = new Hotmart_WooCommerce();
                $hotmart_woocommerce->wc_custom_process_order_for_existing_user($user->ID, $product_name);
                $hotmart_emails = new Hotmart_Emails();
                $hotmart_emails->send_product_available_email($user->user_email, $user->first_name, $product_name); // Envia um e-mail informando que o produto está disponível.
            }
        } else {
            // Se o status da venda for desconhecido, registra o erro e responde com falha.
            hotmart_log_error('Evento desconhecido: ' . $current_status);
            return new WP_REST_Response(array('message' => 'Evento desconhecido'), 400);
        }
    if (is_wp_error($order)) {
        hotmart_log_error("Erro ao criar pedido durante o webhook: " . $order->get_error_message(), false, true);
        return new WP_REST_Response(array('message' => 'Failed to create order'), 500);
    }
        // Se tudo ocorrer bem, responde com sucesso.
        return new WP_REST_Response(array('success' => true, 'message' => 'Processed successfully!'), 200);
    }
}
