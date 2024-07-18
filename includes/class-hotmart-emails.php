<?php

require_once plugin_dir_path(__FILE__) . 'hotmart-functions.php';

/**
 * Classe responsável por enviar e-mails relacionados às compras da Hotmart.
 */
class Hotmart_Emails {

    /**
     * Envia um e-mail de boas-vindas ao usuário com detalhes de login.
     *
     * @param string $email E-mail do usuário.
     * @param string $first_name Primeiro nome do usuário.
     * @param string $password Senha do usuário.
     */
    public function send_welcome_email($email, $first_name, $password) {
        $subject = 'Bem-vindo ao nosso site!';
        $message = "Olá $first_name, Aqui estão seus detalhes de acesso:\nE-mail: $email\nSenha: $password\n\nAcesse agora em: https://academiadoimportador.com.br/cursos/wp-login.php e comece a aprender!";
        wp_mail($email, $subject, $message);
    }

    /**
     * Envia um e-mail ao usuário informando que um novo produto foi adicionado à sua conta.
     *
     * @param string $user_email E-mail do usuário.
     * @param string $user_name Nome do usuário.
     * @param string $product_name Nome do produto adicionado.
     */
    public function send_product_available_email($user_email, $user_name, $product_name) {
        $login_url = 'https://academiadoimportador.com.br/cursos/wp-login.php';
        $reset_password_url = 'https://academiadoimportador.com.br/cursos/wp-login.php?action=lostpassword';
        $instructions_url = 'https://academiadoimportador.com.br/login-academia-do-importador/';
        $subject = 'Seu novo curso foi adicionado à sua conta!';
        $message = "<p>Olá $user_name,</p>\n\n" .
                   "<p>O curso '$product_name' foi adicionado à sua conta. Você já pode acessá-lo em sua área de membros.</p>\n\n" .
                   "<p>Acesse a plataforma: <a href='$login_url'>$login_url</a></p>\n\n" .
                   "<p>Se você não lembra seus dados de acesso, <a href='$reset_password_url'>clique aqui</a> para redefinir a sua senha ou veja as instruções no link a seguir: <a href='$instructions_url'>$instructions_url</a></p>\n\n" .
                   "<p>Equipe</p>";
        wp_mail($user_email, $subject, $message, array('Content-Type: text/html; charset=UTF-8'));
    }

    /**
     * Envia um e-mail de notificação de erro crítico para o administrador do site.
     *
     * @param string $error_message A mensagem de erro a ser enviada.
     */
    public function hotmart_send_error_email($error_message) {
        $error_email = get_option('hotmart_error_email', get_option('admin_email')); 
        $subject = "Erro Crítico no Plugin hotmart";
        $body = "Um erro crítico ocorreu no plugin hotmart: \n\n" . $error_message;
        wp_mail($error_email, $subject, $body);
    }
}
