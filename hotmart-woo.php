<?php
/*
Plugin Name: WooCommerce - Integrador de compras Hotmart
Description: Plugin para integrar as compras feitas na Hotmart com o WooCommerce.
Author: Filipe Barcellos
Version: 1.0
*/

// Inclui os arquivos das classes
require_once plugin_dir_path(__FILE__) . 'includes/class-hotmart-webhook.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-hotmart-emails.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-hotmart-woocommerce.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-hotmart-wordpress.php';

// Inicializa as classes
new Hotmart_Webhook();
new Hotmart_Emails();
new Hotmart_WooCommerce();
new Hotmart_WordPress();
