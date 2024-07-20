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
// Verifica se o diretório do log existe, se não, tenta criar
$log_dir = dirname($log_file);
if (!is_dir($log_dir)) {
mkdir($log_dir, 0755, true);
}

// Verifica se o arquivo de log existe e é gravável
if (!file_exists($log_file) || !is_writable($log_file)) {
error_log("Hotmart Error: Unable to write to log file ($log_file).");
return;
}

$message = date('Y-m-d H:i:s') . ' - ' . $error_message . ' - ';
if ($log_raw_data) {
$message .= esc_html(print_r($error_data, true)) . "\n"; // Sanitização dos dados
} else {
$message .= json_encode($error_data) . "\n";
}

// Tenta escrever no arquivo de log, tratando possíveis erros
try {
error_log($message, 3, $log_file);

// Chamar a função de envio de e-mail
$hotmart_emails = new Hotmart_Emails();
$hotmart_emails->hotmart_send_error_email($error_message, $error_data);
} catch (Exception $e) {
error_log("Hotmart Error: Failed to write to log file. " . $e->getMessage());
}
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
