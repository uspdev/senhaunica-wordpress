<?php

/**
 * Plugin Name:       Senha Única USP
 * Description:       Login and Register your users using OAuth 1 USP
 * Version:           2.0.0
 * Text Domain:       alkaweb
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * GitHub Plugin URI: https://github.com/uspdve/senhaunica-wordpress
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';

class UspdevSenhaUnicaWP
{
    public function __construct() {

        /** Rota para gerar url para login, deve ser cadastrada no retorno
          * /?rest_route=/senhaunica/login 
          **/
        add_action('rest_api_init', function() {
	        register_rest_route('senhaunica', 'login', [
		        'methods' => 'GET',
		        'callback' => array ($this, 'login')
	        ]);
        });
    }

    public function login() {

        if(!session_id()) {
            session_start();
        }

        // Pegar os dados retornados
        $userSenhaUnica = Uspdev\Senhaunica\Senhaunica::login([
            'identifier' => SENHAUNICA_KEY,
            'secret' => SENHAUNICA_SECRET,
            'callback_id' => SENHAUNICA_CALLBACK_ID,
        ]);
        $user = [
            'codpes' => $userSenhaUnica['loginUsuario'],
            'email'  => $userSenhaUnica['emailPrincipalUsuario'],
            'nome'   => $userSenhaUnica['nomeUsuario']
        ];

        if(in_array($user['codpes'],explode(',',SENHAUNICA_PERMITIDOS))){
            $this->loginUser($user);
        }
        
        // Redirect the user
        header("Location: ". home_url() , true);
        die();
    }

  /**
     * Login an user to WordPress
     *
     * @link https://codex.wordpress.org/Function_Reference/get_users
     * @return bool|void
     */
    private function loginUser($user) {

        $wp_users = get_users(array(
            'meta_key'     => 'codpes',
            'meta_value'   => $user['codpes'],
            'number'       => 1,
            'count_total'  => false,
            'fields'       => 'id',
        ));

        #var_dump($wp_users); die();

        # Se usuário não existe, o criamos
        if(empty($wp_users[0])) {
            // Creating our user
            $new_user = wp_create_user($user['codpes'], wp_generate_password(), $user['email']);

            if(is_wp_error($new_user)) {
                var_dump($new_user->get_error_message());
                $_SESSION['senhaunica_message'] = $new_user->get_error_message();
                #header("Location: " . home_url() , true);
                die('deu erro na criação do usuário');
            }

            // Setting the meta
            $nome_array = explode(' ',$user['nome']);
            $first = $nome_array[0];
            $last = implode(' ',array_shift($nome_array));
            update_user_meta( $new_user, 'first_name', $first);
            update_user_meta( $new_user, 'last_name', $last );
            update_user_meta( $new_user, 'codpes', $user['codpes']);
            $obj_user = get_user_by( 'id', $new_user );
            $user_id = $new_user;
        } else {
            $obj_user = get_user_by( 'id', $wp_users[0] );
            $user_id = $wp_users[0];
        }
        $obj_user->add_role( 'administrator' );

        wp_set_auth_cookie( $user_id );
    }
}

new UspdevSenhaUnicaWP;

