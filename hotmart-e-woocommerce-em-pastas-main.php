<?php
/*
Plugin Name: WooCommerce e Hotmart por FILIPE BARCELLOS
Description: Plugin para integrar as compras feitas na Hotmart com o WooCommerce.
Author: Filipe Barcellos
Version: 1.0
*/

// Define a constante para o caminho da pasta includes
define('HOTMART_PLUGIN_INCLUDES_DIR', plugin_dir_path(__FILE__) . 'includes/');

// Inclui o arquivo de funções auxiliares
require_once HOTMART_PLUGIN_INCLUDES_DIR . 'hotmart-functions.php';

// Função hotmart_log_error movida para o arquivo principal
function hotmart_log_error($error_message, $error_data = array()) {
    $log_file = WP_CONTENT_DIR . '/hotmart.log';
    $log_enabled = get_option('hotmart_logging_enabled', 'no') === 'yes';
    $log_raw_data = get_option('hotmart_log_raw_data', 'no') === 'yes';

    if ($log_enabled) {
        $message = date('Y-m-d H:i:s') . ' - ' . $error_message . ' - ';
        if ($log_raw_data) {
            $message .= print_r($error_data, true) . "\n";
        } else {
            $message .= json_encode($error_data) . "\n";
        }
        error_log($message, 3, $log_file);

        // Chamar a função de envio de e-mail
    // Cria uma instância da classe Hotmart_Emails
    $hotmart_emails = new Hotmart_Emails();

    // Chama o método de envio de e-mail através da instância
    $hotmart_emails->hotmart_send_error_email($error_message, $error_data);
}
}

// Inclui os arquivos das classes do plugin
require_once HOTMART_PLUGIN_INCLUDES_DIR . 'class-hotmart-wordpress.php';
require_once HOTMART_PLUGIN_INCLUDES_DIR . 'class-hotmart-emails.php';
require_once HOTMART_PLUGIN_INCLUDES_DIR . 'class-hotmart-woocommerce.php';
require_once HOTMART_PLUGIN_INCLUDES_DIR . 'class-hotmart-webhook.php';

// Inicializa as classes
new Hotmart_Webhook();
new Hotmart_Emails();
new Hotmart_WooCommerce();
new Hotmart_WordPress();
