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
    public static function login($clientCredentials = [])
    {
        if (empty($clientCredentials)) {
            $clientCredentials['identifier'] = getenv('SENHAUNICA_KEY');
            $clientCredentials['secret'] = getenv('SENHAUNICA_SECRET');
            $clientCredentials['callback_id'] = getenv('SENHAUNICA_CALLBACK_ID');
        }

        $server = new ServerUSP($clientCredentials);

        // step 3: tudo ok
        if (isset($_SESSION['token_credentials'])) {
            $tokenCredentials = unserialize($_SESSION['token_credentials']);
            $_SESSION['oauth_user'] = $server->getUserDetails($tokenCredentials);
            session_write_close();
            return $_SESSION['oauth_user'];
        }

        // step 2: recebendo o retorno do oauth
        if (isset($_GET['oauth_token']) && isset($_GET['oauth_verifier'])) {
            $temporaryCredentials = unserialize($_SESSION['temporary_credentials']);
            $tokenCredentials = $server->getTokenCredentials($temporaryCredentials, $_GET['oauth_token'], $_GET['oauth_verifier']);
            unset($_SESSION['temporary_credentials']);
            $_SESSION['token_credentials'] = serialize($tokenCredentials);
            $_SESSION['oauth_user'] = $server->getUserDetails($tokenCredentials);
            session_write_close();
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }

        // step 1: credenciais temporárias e redirecionamento para login USP
        $temporaryCredentials = $server->getTemporaryCredentials();
        $_SESSION['temporary_credentials'] = serialize($temporaryCredentials);
        session_write_close();
        $url = $server->getAuthorizationUrl($temporaryCredentials) . '&callback_id=' . $clientCredentials['callback_id'];
        header('Location: ' . $url);
        exit;
    }

    public static function getUserDetail()
    {
        return isset($_SESSION['oauth_user']) ? $_SESSION['oauth_user'] : null;
    }

    /**
     * retorna o primeiro vinculo que encontrar
     *
     * com o critério 'campo' == [valor1, ou valor2, ...]
     */
    public static function obterVinculo($campo, $valores)
    {
        if (!isset($_SESSION['oauth_user']['vinculo'])) {
            return null;
        }
        if (!is_array($valores)) {
            $valores = [$valores];
        }
        foreach ($valores as $valor) {
            foreach ($_SESSION['oauth_user']['vinculo'] as $v) {
                if ($v[$campo] == $valor) {
                    return $v;
                }
            }
        }
        return false;
    }
}
