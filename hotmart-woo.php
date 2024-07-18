<?php

define('HOTMART_PLUGIN_INCLUDES_DIR', plugin_dir_path(__FILE__) . 'includes/');

/**
 * Plugin Name: WooCommerce e Hotmart por FILIPE BARCELLOS
 * Description: Plugin para integrar as compras feitas na Hotmart com o WooCommerce.
 * Author: Filipe Barcellos
 * Version: 1.0
 */

// Inclui os arquivos das classes do plugin.
require_once plugin_dir_path(__FILE__) . 'includes/class-hotmart-webhook.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-hotmart-emails.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-hotmart-woocommerce.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-hotmart-wordpress.php';
require_once plugin_dir_path(__FILE__) . 'includes/hotmart-functions.php'; // Inclui o arquivo de funções auxiliares

// Cria instâncias das classes do plugin.
new Hotmart_Webhook();
new Hotmart_Emails();
new Hotmart_WooCommerce();
new Hotmart_WordPress();
