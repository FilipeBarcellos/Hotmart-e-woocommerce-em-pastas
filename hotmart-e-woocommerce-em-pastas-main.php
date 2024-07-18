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

// Inclui os arquivos das classes do plugin
require_once HOTMART_PLUGIN_INCLUDES_DIR . 'class-hotmart-webhook.php';
require_once HOTMART_PLUGIN_INCLUDES_DIR . 'class-hotmart-emails.php';
require_once HOTMART_PLUGIN_INCLUDES_DIR . 'class-hotmart-woocommerce.php';
require_once HOTMART_PLUGIN_INCLUDES_DIR . 'class-hotmart-wordpress.php';

// Inicializa as classes
new Hotmart_Webhook();
new Hotmart_Emails();
new Hotmart_WooCommerce();
new Hotmart_WordPress();
