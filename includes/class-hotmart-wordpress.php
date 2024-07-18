<?php

require_once HOTMART_PLUGIN_INCLUDES_DIR . 'hotmart-functions.php';

class Hotmart_WordPress {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'hotmart_add_admin_menu'));
        add_action('admin_init', array($this, 'hotmart_register_settings'));
    }

    /**
     * Adiciona uma página de menu ao painel de administração do WordPress para o plugin.
     */
    public function hotmart_add_admin_menu() {
        add_menu_page('Webhook hotmart', 'Webhook hotmart', 'manage_options', 'hotmart_webhook', array($this, 'hotmart_options_page')); 
        add_submenu_page('hotmart_webhook', 'Log de Erros', 'Log de Erros', 'manage_options', 'hotmart_error_log', array($this, 'hotmart_display_error_log'));
    }

    /**
     * Exibe a página de opções do plugin no painel de administração.
     */
    public function hotmart_options_page() {
        // Verifica se o usuário deseja limpar o log e tem a permissão para isso
        if (isset($_POST['hotmart_clear_log']) && check_admin_referer('hotmart_clear_log_action', 'hotmart_clear_log_nonce')) {
            $log_file_path = get_option('hotmart_log_file_path', plugin_dir_path(dirname(__FILE__)) . 'hotmart.log');
            file_put_contents($log_file_path, '');
            echo "<div class='updated'><p>Log limpo.</p></div>";
        }
        ?>
        <div class="wrap">
            <h2>Configurações do Webhook hotmart</h2>
            <form action="options.php" method="post">
                <?php
                settings_fields('hotmart_logger_options');
                do_settings_sections('hotmart_logger');
                submit_button();
                ?>
            </form>
            <form action="" method="post">
                <?php
                wp_nonce_field('hotmart_clear_log_action', 'hotmart_clear_log_nonce');
                submit_button('Limpar Log', 'delete', 'hotmart_clear_log', false);
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Registra as configurações do plugin, como a opção de habilitar o registro de log
     * e o caminho do arquivo de log. Define as seções e campos na página de configurações.
     */
    public function hotmart_register_settings() {
        register_setting('hotmart_logger_options', 'hotmart_logging_enabled');
        register_setting('hotmart_logger_options', 'hotmart_log_file_path');
        add_settings_section('hotmart_logger_main', 'Configurações principais', array($this, 'hotmart_logger_section_text'), 'hotmart_logger');
        add_settings_field('hotmart_logging_enabled', 'Habilitar registro', array($this, 'hotmart_logging_enabled_field'), 'hotmart_logger', 'hotmart_logger_main');
        add_settings_field('hotmart_log_file_path', 'Caminho do arquivo de log', array($this, 'hotmart_log_file_path_field'), 'hotmart_logger', 'hotmart_logger_main');
        add_settings_field('hotmart_log_contents', 'Conteúdo do Log', array($this, 'hotmart_log_contents_field'), 'hotmart_logger', 'hotmart_logger_main');
        register_setting('hotmart_logger_options', 'hotmart_log_raw_data');
        add_settings_field('hotmart_log_raw_data', 'Registrar Dados Brutos', array($this, 'hotmart_log_raw_data_field'), 'hotmart_logger', 'hotmart_logger_main');
        register_setting('hotmart_logger_options', 'hotmart_error_email');
        add_settings_field('hotmart_error_email', 'E-mail para Notificações de Erro', array($this, 'hotmart_error_email_field'), 'hotmart_logger', 'hotmart_logger_main');

    }

    /**
     * Função para exibir texto introdutório para a seção de configurações principais.
     */
    public function hotmart_logger_section_text() {
        echo '<p>Configuração principal do Webhook hotmart.</p>';
    }

    /**
     * Campo para definir o caminho do arquivo de log na página de configurações.
     */
public function hotmart_log_file_path_field() {
    // Obtém o diretório raiz do plugin
    $plugin_dir = plugin_dir_path( dirname( __FILE__ ) );  

    // Define o caminho completo do arquivo de log na raiz do plugin
    $log_file_path = $plugin_dir . 'hotmart.log'; 

    echo "<input id='hotmart_log_file_path' name='hotmart_log_file_path' type='text' value='" . esc_attr($log_file_path) . "' />";
}


    /**
     * Campo para exibir o conteúdo do arquivo de log na página de configurações.
     */
    public function hotmart_log_contents_field() {
        $log_file_path = get_option('hotmart_log_file_path', plugin_dir_path(dirname(__FILE__)) . 'hotmart.log');
        if (file_exists($log_file_path)) {
            echo "<textarea readonly rows='10' cols='70'>" . esc_textarea(file_get_contents($log_file_path)) . "</textarea>";
        } else {
            echo "<p>Arquivo de log não encontrado. Verifique o caminho ou as permissões.</p>";
        }
    }

    /**
     * Campo para habilitar ou desabilitar o registro de log na página de configurações.
     */
    public function hotmart_logging_enabled_field() {
        $logging_enabled = get_option('hotmart_logging_enabled', 'no');
        echo "<input id='hotmart_logging_enabled' name='hotmart_logging_enabled' type='checkbox' " . checked('yes', $logging_enabled, false) . " value='yes'> ";
    }

    /**
     * Campo para habilitar ou desabilitar o registro de dados brutos no log.
     */
    public function hotmart_log_raw_data_field() {
        $log_raw_data = get_option('hotmart_log_raw_data', 'no');
        echo "<input id='hotmart_log_raw_data' name='hotmart_log_raw_data' type='checkbox' " . checked('yes', $log_raw_data, false) . " value='yes'> Registrar dados brutos recebidos no log";
    }

    /**
     * Campo para definir o e-mail para notificações de erro.
     */
    public function hotmart_error_email_field() {
        $error_email = get_option('hotmart_error_email', '');
        echo "<input id='hotmart_error_email' name='hotmart_error_email' type='email' value='" . esc_attr($error_email) . "' />";
    }

    /**
     * Função para exibir o log de erros.
     */
    public function hotmart_display_error_log() {
        $log_file_path = get_option('hotmart_log_file_path', plugin_dir_path(dirname(__FILE__)) . 'hotmart.log');
        if (file_exists($log_file_path)) {
            echo "<h2>Log de Erros</h2>";
            echo "<textarea readonly rows='20' cols='100'>" . esc_textarea(file_get_contents($log_file_path)) . "</textarea>";
        } else {
            echo "<p>Arquivo de log não encontrado. Verifique o caminho ou as permissões.</p>";
        }
    }

    // Função hotmart_log_error() adicionada
    public function hotmart_log_error($error_message, $error_data = array()) {
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
    hotmart_send_error_email_webhook($error_message, $error_data);
        }
    }
}
