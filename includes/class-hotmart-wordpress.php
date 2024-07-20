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
    // Verifica se o usuário deseja limpar algum log e tem a permissão para isso
    $logs_to_clear = [
        'hotmart_clear_log' => [
            'nonce_action' => 'hotmart_clear_log_action',
            'nonce_name' => 'hotmart_clear_log_nonce',
            'file_path_option' => 'hotmart_log_file_path',
            'default_file_path' => plugin_dir_path(dirname(__FILE__)) . 'hotmart.log',
            'success_message' => 'Log limpo.',
            'error_message' => 'Erro ao limpar o log. Verifique as permissões do arquivo.',
        ],
        'hotmart_clear_raw_data_log' => [
            'nonce_action' => 'hotmart_clear_raw_data_log_action',
            'nonce_name' => 'hotmart_clear_raw_data_log_nonce',
            'file_path_option' => 'hotmart_raw_data_log_file_path',
            'default_file_path' => plugin_dir_path(dirname(__FILE__)) . 'hotmart_raw_data.log',
            'success_message' => 'Log de dados brutos limpo.',
            'error_message' => 'Erro ao limpar o log de dados brutos. Verifique as permissões do arquivo.',
        ],
    ];

    foreach ($logs_to_clear as $clear_action => $log_info) {
        if (isset($_POST[$clear_action]) && check_admin_referer($log_info['nonce_action'], $log_info['nonce_name'])) {
            $log_file_path = get_option($log_info['file_path_option'], $log_info['default_file_path']);

            if (file_exists($log_file_path) && is_writable($log_file_path)) {
                file_put_contents($log_file_path, '');
                echo "<div class='updated'><p>{$log_info['success_message']}</p></div>";
            } else {
                echo "<div class='error'><p>{$log_info['error_message']}</p></div>";
            }
        }
    }

    ?>
    <div class="wrap">
        <h2>Configurações do Webhook Hotmart</h2>

        <form action="options.php" method="post">
            <?php
            settings_fields('hotmart_logger_options');
            do_settings_sections('hotmart_logger');
            submit_button();
            ?>
        </form>

        <h3>Conteúdo do Log Principal:</h3>
        <div>
            <?php $this->hotmart_log_contents_field(); ?>
        </div>
        <form action="" method="post" style="margin-top: 10px;">
            <?php
            wp_nonce_field('hotmart_clear_log_action', 'hotmart_clear_log_nonce');
            submit_button('Limpar Log Principal', 'delete', 'hotmart_clear_log', false);
            ?>
        </form>

        <h3>Conteúdo do Log de Dados Brutos:</h3>
        <div>
            <?php $this->hotmart_raw_data_log_contents_field(); ?>
        </div>
        <form action="" method="post" style="margin-top: 10px;">
            <?php
            wp_nonce_field('hotmart_clear_raw_data_log_action', 'hotmart_clear_raw_data_log_nonce');
            submit_button('Limpar Log de Dados Brutos', 'delete', 'hotmart_clear_raw_data_log', false);
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
        register_setting('hotmart_logger_options', 'hotmart_log_raw_data');
        add_settings_field('hotmart_log_raw_data', 'Registrar Dados Brutos', array($this, 'hotmart_log_raw_data_field'), 'hotmart_logger', 'hotmart_logger_main');
        register_setting('hotmart_logger_options', 'hotmart_error_email');
        add_settings_field('hotmart_error_email', 'E-mail para Notificações de Erro', array($this, 'hotmart_error_email_field'), 'hotmart_logger', 'hotmart_logger_main');
        register_setting('hotmart_logger_options', 'hotmart_raw_data_log_file_path');
        add_settings_field('hotmart_raw_data_log_file_path', 'Caminho do arquivo de log de dados brutos', array($this, 'hotmart_raw_data_log_file_path_field'), 'hotmart_logger', 'hotmart_logger_main');


    }
  
  
      public function hotmart_raw_data_log_file_path_field() {
        $log_file_path = get_option('hotmart_raw_data_log_file_path', plugin_dir_path( dirname( __FILE__ ) ) . 'hotmart_raw_data.log'); 
        echo "<input id='hotmart_raw_data_log_file_path' name='hotmart_raw_data_log_file_path' type='text' value='" . esc_attr($log_file_path) . "' />";
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

        if (file_exists($log_file_path) && is_readable($log_file_path)) {
            $log_contents = file_get_contents($log_file_path);
            echo "<textarea readonly rows='10' cols='70'>" . esc_html($log_contents) . "</textarea>";
        } else {
            echo "<p>Arquivo de log não encontrado ou não pode ser lido.</p>";
        }
    }

    public function hotmart_logging_enabled_field() {
        $logging_enabled = get_option('hotmart_logging_enabled', 'no');
        echo "<input id='hotmart_logging_enabled' name='hotmart_logging_enabled' type='checkbox' " . checked('yes', $logging_enabled, false) . " value='yes'> ";
    }

    public function hotmart_log_raw_data_field() {
        $log_raw_data = get_option('hotmart_log_raw_data', 'no');
        echo "<input id='hotmart_log_raw_data' name='hotmart_log_raw_data' type='checkbox' " . checked('yes', $log_raw_data, false) . " value='yes'> Registrar dados brutos recebidos no log";
    }

    public function hotmart_error_email_field() {
        $error_email = get_option('hotmart_error_email', '');
        echo "<input id='hotmart_error_email' name='hotmart_error_email' type='email' value='" . esc_attr($error_email) . "' />";
    }


  public function hotmart_raw_data_log_contents_field() {
    $raw_data_log_file_path = get_option('hotmart_raw_data_log_file_path', plugin_dir_path(dirname(__FILE__)) . 'hotmart_raw_data.log');

    if (file_exists($raw_data_log_file_path) && is_readable($raw_data_log_file_path)) {
        $log_contents = file_get_contents($raw_data_log_file_path);
        echo "<textarea readonly rows='10' cols='70'>" . esc_html($log_contents) . "</textarea>";
    } else {
        echo "<p>Arquivo de log de dados brutos não encontrado ou não pode ser lido.</p>";
    }
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

  
public function hotmart_log_raw_data($data) {
    $log_raw_data = get_option('hotmart_log_raw_data', 'no') === 'yes';
    $raw_data_log_file = WP_CONTENT_DIR . '/hotmart_raw_data.log';

    if ($log_raw_data) {
        // Verifica se o diretório do log existe, se não, tenta criar
        $log_dir = dirname($raw_data_log_file);
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }

        // Verifica se o arquivo de log existe e é gravável
        if (!file_exists($raw_data_log_file) || !is_writable($raw_data_log_file)) {
            error_log("Hotmart Error: Unable to write to raw data log file ($raw_data_log_file).");
            return;
        }

        // Filtra os dados brutos para incluir apenas os campos desejados (usando a seta "->" para acessar as propriedades do objeto)
        $filtered_data = array(
            'buyer_email' => $data->buyer->email ?? '',
            'buyer_name' => $data->buyer->name ?? '',
            'product_name' => $data->product->name ?? '',
            'transaction' => $data->purchase->transaction ?? '',
            'status' => $data->status ?? '',
            'date' => $data->purchase->date ?? ''
        );

        $message = date('Y-m-d H:i:s') . ' - ' . esc_html(print_r($filtered_data, true)) . "\n";
        error_log($message, 3, $raw_data_log_file);
    }
}
} 
