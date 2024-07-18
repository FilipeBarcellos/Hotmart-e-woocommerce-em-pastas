<?php
/**
 * Plugin Name: WooCommerce - Integrador de compras Hotmart
 * Description: Plugin para integrar as compras feitas na Hotmart com o WooCommerce.
 * Author: Filipe Barcellos
 * Version: 1.0
 */

// Inclui os arquivos das classes do plugin.
// Cada classe é responsável por uma parte específica da funcionalidade do plugin.
require_once plugin_dir_path(__FILE__) . 'includes/class-hotmart-webhook.php'; // Lida com o recebimento e processamento de webhooks da Hotmart.
require_once plugin_dir_path(__FILE__) . 'includes/class-hotmart-emails.php';   // Gerencia o envio de e-mails relacionados às compras.
require_once plugin_dir_path(__FILE__) . 'includes/class-hotmart-woocommerce.php'; // Realiza a integração com o WooCommerce (criação de pedidos, clientes, etc.).
require_once plugin_dir_path(__FILE__) . 'includes/class-hotmart-wordpress.php';  // Lida com as configurações do plugin no painel do WordPress.
require_once plugin_dir_path(__FILE__) . 'includes/hotmart-functions.php';


// Cria instâncias das classes do plugin.
// Isso permite que as funções e métodos das classes sejam utilizados.
new Hotmart_Webhook();
new Hotmart_Emails();
new Hotmart_WooCommerce();
new Hotmart_WordPress();
