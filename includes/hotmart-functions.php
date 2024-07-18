<?php

/**
 * Registra mensagens de erro em um arquivo de log se o registro estiver habilitado nas opções do plugin.
 *
 * @param string $message Mensagem de erro para registrar.
 * @param bool $log_raw_data Se deve registrar dados brutos.
 * @param bool $is_critical Se o erro é crítico.
 * @param array|string $extra_context Contexto extra para adicionar ao log.
 */
function hotmart_log_error($message, $log_raw_data = false, $is_critical = false, $extra_context = '') {
    if (get_option('hotmart_logging_enabled', 'no') === 'yes') {
        if ($log_raw_data && get_option('hotmart_log_raw_data', 'no') !== 'yes') {
            return; // Não registra se a opção de dados brutos não estiver habilitada.
        }
        $log_file_path = get_option('hotmart_log_file_path', plugin_dir_path(__FILE__) . 'hotmart.log');
        if (!$log_file_path) {
            $log_file_path = plugin_dir_path(__FILE__) . 'hotmart.log';
        }

        $date = date("Y-m-d H:i:s");
        $log_entry = sprintf("[%s] %s", $date, is_array($message) || is_object($message) ? print_r($message, true) : $message);

        // Adiciona contexto extra ao log, se fornecido
        if (!empty($extra_context)) {
            $log_entry .= " | Contexto Extra: " . (is_array($extra_context) || is_object($extra_context) ? print_r($extra_context, true) : $extra_context);
        }

        $log_entry .= "\n";
        error_log($log_entry, 3, $log_file_path);

        if ($is_critical) {
            $hotmart_emails = new Hotmart_Emails(); // Cria uma instância da classe Hotmart_Emails
            $hotmart_emails->hotmart_send_error_email($message); // Envia o e-mail de erro
        }
    }
}

/**
 * Divide um nome completo em primeiro e último nome.
 *
 * @param string $full_name Nome completo.
 * @return array Array com o primeiro e último nome.
 */
function split_full_name($full_name) {
    $parts = explode(' ', $full_name);
    $last_name = array_pop($parts);
    $first_name = implode(' ', $parts);
    return array($first_name, $last_name);
}
