<?php

namespace Uspdev\Senhaunica;

use Uspdev\Senhaunica\ServerUSP;

/**
 * Senhaunica: classe para autenticar senha única da USP
 *
 * @author masakik
 *
 */
class Senhaunica
{
    /**
     * Namespace da SESSION
     */
    const NS = 'uspdev-senhaunica';

    /**
     * Método que verifica e executa o login
     * 
     * Processo do oauth: 
     * primeiro processa step 1, que direciona o usuário para a página de login do Oauth;
     * o retorno do oauth é direcionado para cá novamente que processa step 2 e reotrna dados do oauth;
     * o step 3 é para o caso de solicitar o login novamente porém já estiver autenticado. 
     *   Nesse caso pega as informações do usuário novamente e 
     *   retorna as informações para a rota que chamou inicialmente
     * 
     * @param $clientCredentials
     * @return Null|Array
     */
    public static function login($clientCredentials = [])
    {
        if(session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // se não passou as credentials por parâmetro vamos buscar no env
        if (empty($clientCredentials)) {
            $clientCredentials = [
                'identifier' => getenv('SENHAUNICA_KEY'),
                'secret' => getenv('SENHAUNICA_SECRET'),
                'callback_id' => getenv('SENHAUNICA_CALLBACK_ID'),
            ];
        }
        $server = new ServerUSP($clientCredentials);

        // step 3: tudo ok
        if (isset($_SESSION[self::NS]['token_credentials'])) {
            $tokenCredentials = unserialize($_SESSION[self::NS]['token_credentials']);
            $_SESSION[self::NS]['oauth_user'] = $server->getUserDetails($tokenCredentials);
            return $_SESSION[self::NS]['oauth_user'];
        }

        // step 2: recebendo o retorno do oauth
        if (isset($_GET['oauth_token']) && isset($_GET['oauth_verifier'])) {
            $temporaryCredentials = unserialize($_SESSION[self::NS]['temporary_credentials']);
            $tokenCredentials = $server->getTokenCredentials($temporaryCredentials, $_GET['oauth_token'], $_GET['oauth_verifier']);
            unset($_SESSION[self::NS]['temporary_credentials']);
            $_SESSION[self::NS]['token_credentials'] = serialize($tokenCredentials);
            $_SESSION[self::NS]['oauth_user'] = $server->getUserDetails($tokenCredentials);
            return $_SESSION[self::NS]['oauth_user'];
        }

        // step 1: credenciais temporárias e redirecionamento para login USP
        $temporaryCredentials = $server->getTemporaryCredentials();
        $_SESSION[self::NS]['temporary_credentials'] = serialize($temporaryCredentials);
        $url = $server->getAuthorizationUrl($temporaryCredentials) . '&callback_id=' . $clientCredentials['callback_id'];
        header('Location: ' . $url);
        exit;
    }

    /**
     * Limpa as variáveis de sessão do oauth para forçar novamente a autenticação.
     */
    public static function logout() {
        unset($_SESSION[self::NS]);
    }

    /**
     * Retorna os dados do usuário que estão na session ou null se não disponível
     * 
     * Fica para a aplicação decidir se vai fazer login novamente ou tratar de outra forma.
     */
    public static function getUserDetail()
    {
        return isset($_SESSION[self::NS]['oauth_user']) ? $_SESSION[self::NS]['oauth_user'] : null;
    }

    /**
     * retorna o primeiro vinculo que encontrar
     *
     * com o critério 'campo' == [valor1, ou valor2, ...]
     */
    public static function obterVinculo($campo, $valores)
    {
        if (!isset($_SESSION[self::NS]['oauth_user']['vinculo'])) {
            return null;
        }
        if (!is_array($valores)) {
            $valores = [$valores];
        }
        foreach ($valores as $valor) {
            foreach ($_SESSION[self::NS]['oauth_user']['vinculo'] as $v) {
                if ($v[$campo] == $valor) {
                    return $v;
                }
            }
        }
        return false;
    }
}
