<?php
require_once HOTMART_PLUGIN_INCLUDES_DIR . 'hotmart-functions.php';
require_once HOTMART_PLUGIN_INCLUDES_DIR . 'class-hotmart-wordpress.php';
require_once HOTMART_PLUGIN_INCLUDES_DIR . 'hotmart-config.php';

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
    $data_raw = $request->get_body();
    hotmart_log_error("Dados brutos recebidos: " . $data_raw, true);

    // Decodifica o JSON em um objeto PHP
    $data = json_decode($data_raw);

    // Validação do hottok 
    $hottok_recebido = $request->get_header('X-Hotmart-Hottok');
    if (!$hottok_recebido) {
        $hottok_recebido = $data->hottok; // Obtém do corpo do JSON
    }

    if (!$hottok_recebido || $hottok_recebido !== HOTMART_WEBHOOK_TOKEN) {
        hotmart_log_error('Hottok inválido ou ausente: ' . $hottok_recebido);
        return new WP_REST_Response(array('message' => 'Acesso não autorizado: hottok inválido ou ausente'), 403);
    }

    // Extrai os dados do objeto data do webhook para uma nova variável
    $webhookData = $data->data;

    // Verificação dos campos obrigatórios (corrigido)
    $required_keys = ['buyer.name', 'buyer.email', 'product.name', 'event', 'purchase.transaction'];
    $missing_keys = []; 

    foreach ($required_keys as $key) {
        $keys = explode('.', $key);
        if (count($keys) > 1) { // Campo aninhado
            $value = $webhookData; // Usa a nova variável $webhookData
            foreach ($keys as $k) {
                if (!isset($value->$k)) { 
                    $missing_keys[] = $key;
                    break; 
                }
                $value = $value->$k;
            }
        } else { // Campo não aninhado (como event)
            if (!isset($data->$key)) { // Verifica em $data, que ainda contém o evento
                $missing_keys[] = $key;
            }
        }
    }

    // Retorna erro se houver campos ausentes
    if (!empty($missing_keys)) {
        $error_message = "Missing data: " . implode(', ', $missing_keys);
        hotmart_log_error($error_message);
        return new WP_REST_Response(array('message' => $error_message), 400);
    }

    // Extração dos dados do webhook (após a verificação dos campos obrigatórios)
    $buyer_name = isset($webhookData->buyer->name) ? sanitize_text_field($webhookData->buyer->name) : "Comprador não informado";
    $email = sanitize_email($webhookData->buyer->email);
    $product_name = sanitize_text_field($webhookData->product->name); // Corrigido
    $event = $data->event;
    $transaction_id = $webhookData->purchase->transaction;

    // Sanitiza e valida o nome completo do cliente (corrigido)
    $full_name = sanitize_text_field($webhookData->buyer->name);
    if (empty($full_name)) {
        hotmart_log_error('Full name is empty.');
        return new WP_REST_Response(array('message' => 'Full name is empty'), 400);
    }
    list($first_name, $last_name) = hotmart_split_full_name($full_name);
    $username = str_replace(' ', '', strtolower($full_name));


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
$product_name = sanitize_text_field($webhookData->product->name); // Sanitiza o nome do produto. (corrigido)


    // Processa a venda com base no status atual.
    $current_status = $data->event;

    // Verifica se o objeto purchase existe antes de acessar suas propriedades
    if (isset($webhookData->purchase) && is_object($webhookData->purchase)) {
        $transaction_id = $webhookData->purchase->transaction;
    } else {
        $transaction_id = null;
    }

    if ($current_status == "PURCHASE_REFUNDED" || $current_status == "PURCHASE_CHARGEBACK") {
        if ($transaction_id) { 
            $order = wc_get_order(hotmart_get_order_id_by_transaction_id($transaction_id));

            if ($order) {
                $hotmart_woocommerce = new Hotmart_WooCommerce();
                $hotmart_woocommerce->wc_custom_refund_order_by_id_da_transacao($transaction_id); 
            } else {
                hotmart_log_error("Pedido não encontrado para o ID da transação: " . $transaction_id);
            }
        } 
    } elseif ($current_status == "PURCHASE_APPROVED") { // if do elseif adicionado aqui
        $user = get_user_by('email', $email);
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
        $order = $hotmart_woocommerce->wc_custom_create_order_hotmart(array('status' => 'completed', 'customer_id' => $user_id), $first_name, $email, $product_name, $webhookData->purchase->transaction); // Corrigido
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
        hotmart_log_error('Evento desconhecido: ' . $current_status);
        return new WP_REST_Response(array('message' => 'Evento desconhecido'), 400);
    }

    // Verificação final do $order (movido para o final do método)
    if (is_wp_error($order)) {
        hotmart_log_error("Erro ao criar pedido durante o webhook: " . $order->get_error_message(), false, true);
        return new WP_REST_Response(array('message' => 'Failed to create order'), 500);
    }

    // Se tudo ocorrer bem, responde com sucesso.
    return new WP_REST_Response(array('success' => true, 'message' => 'Processed successfully!'), 200);
    } 
}
