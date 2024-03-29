<?php

/**
 * Plugin Name:       Senha Única USP
 * Description:       Login and Register your users using OAuth 1 USP
 * Version:           2.0.3
 * Text Domain:       uspdev-senhaunica-wordpress
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * GitHub Plugin URI: https://github.com/uspdev/senhaunica-wordpress
 */

/* Exit if accessed directly */
if (!defined('ABSPATH')) {
    return;
}

class UspdevSenhaunicaWP
{
    public function __construct()
    {
        /** Rota para gerar url para login, deve ser cadastrada no retorno
         * /?rest_route=/senhaunica/login
         **/
        add_action('rest_api_init', function () {
            register_rest_route('senhaunica', 'login', [
                'methods' => 'GET',
                'callback' => [$this, 'login'],
                'permission_callback' => '',
            ]);
        });

        // logout remove as variáveis da session
        add_action('rest_api_init', function () {
            register_rest_route('senhaunica', 'logout', [
                'methods' => 'GET',
                'callback' => [$this, 'logout'],
                'permission_callback' => '',
            ]);
        });

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public function login()
    {
        require __DIR__ . '/vendor/autoload.php';
        require __DIR__ . '/config.php';

        // Pegar os dados retornados
        $userSenhaUnica = Uspdev\Senhaunica\Senhaunica::login($clientCredentials);

        // se houver autorizados na constante SENHAUNICA_ADMINS, vamos utilizar também
        if (defined('SENHAUNICA_ADMINS')) {
            $senhaunicaAdmins = array_merge($senhaunicaAdmins, explode(',', SENHAUNICA_ADMINS));
        }

        // vamos fazer login no wordpress se autorizado
        if (in_array($userSenhaUnica['loginUsuario'], $senhaunicaAdmins)) {
            $this->loginUser([
                'codpes' => $userSenhaUnica['loginUsuario'],
                'email' => $userSenhaUnica['emailPrincipalUsuario'],
                'nome' => $userSenhaUnica['nomeUsuario'],
            ]);
        }

        // vamos chamar os callbacks definidos no config
        foreach ($callbacks as $callback) {
            call_user_func($callback);
        }

        // vamos redirecionar para a última página visitada, se for o caso
        if (isset($_SESSION['next'])) {
            header('Location: ' . home_url($_SESSION['next']), true);
            unset($_SESSION['next']);
            die();
        }

        // Se não direcionar antes, vamos direcionar para a página default
        header('Location: ' . home_url($redirect), true);
        die();
    }

    public function logout()
    {
        require_once __DIR__ . '/vendor/autoload.php';
        require_once __DIR__ . '/config.php';

        Uspdev\Senhaunica\Senhaunica::logout();
        header("Location: " . home_url('/'), true);
        die();
    }

    public static function obterUsuario()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        require_once __DIR__ . '/vendor/autoload.php';
        require_once __DIR__ . '/config.php';

        $userSenhaUnica = Uspdev\Senhaunica\Senhaunica::getUserDetail();
        if (!$userSenhaUnica) {
            $_SESSION['next'] = get_page_uri();
            $senhaunicaWP = new self;
            $userSenhaUnica = $senhaunicaWP->login();
            die();
        }
        return $userSenhaUnica;
    }

    /**
     * Login an user to WordPress
     *
     * @link https://codex.wordpress.org/Function_Reference/get_users
     * @return bool|void
     */
    private function loginUser($user)
    {
        // procura usuário existente com mesmo codpes
        $wp_users = get_users(array(
            'meta_key' => 'codpes',
            'meta_value' => $user['codpes'],
            'number' => 1,
            'count_total' => false,
            'fields' => 'id',
        ));

        # Se usuário não existe, o criamos
        if (empty($wp_users[0])) {
            // Creating our user
            $new_user = wp_create_user($user['codpes'], wp_generate_password(), $user['email']);

            if (is_wp_error($new_user)) {
                var_dump($new_user->get_error_message());
                $_SESSION['senhaunica_message'] = $new_user->get_error_message();
                die('Deu erro na criação do usuário');
            }

            // Setting the meta
            $nome_array = explode(' ', $user['nome']);
            $first = $nome_array[0];
            array_shift($nome_array);
            $last = implode(' ', $nome_array);
            update_user_meta($new_user, 'first_name', $first);
            update_user_meta($new_user, 'last_name', $last);
            update_user_meta($new_user, 'codpes', $user['codpes']);
            $obj_user = get_user_by('id', $new_user);
            $user_id = $new_user;

        } else {
            $obj_user = get_user_by('id', $wp_users[0]);
            $user_id = $wp_users[0];
        }
        $obj_user->add_role('administrator');

        wp_set_auth_cookie($user_id);
    }
}

new UspdevSenhaunicaWP();
