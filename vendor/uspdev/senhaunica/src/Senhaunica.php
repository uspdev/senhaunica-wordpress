<?php

/**
 * Senhaunica: classe para autenticar contra senha única da USP
 *
 * @author masakik
 *
 */

namespace Uspdev\Senhaunica;

class Senhaunica
{

    protected $curl_options = array(
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1
    );

    public function __construct($oauth)
    {
        if ($oauth['amb'] == 1 || $oauth['amb'] == 'dev') {
            $oauth_host = "https://dev.uspdigital.usp.br"; // ambiente de teste
        } elseif ($oauth['amb'] == 2 || $oauth['amb'] == 'prod') {
            $oauth_host = "https://uspdigital.usp.br"; // ambiente de producao
        } else {
            echo 'Ambiente não configurado!';
            exit;
        }

        $this->user_data_url = $oauth_host . "/wsusuario/oauth/usuariousp";

        //  Init the OAuthStore
        $this->options = array(
            'consumer_key' => $oauth['consumer_key'],
            'consumer_secret' => $oauth['consumer_secret'],
            'server_uri' => $oauth_host,
            'request_token_uri' => $oauth_host . "/wsusuario/oauth/request_token",
            'authorize_uri' => $oauth_host . "/wsusuario/oauth/authorize",
            'access_token_uri' => $oauth_host . "/wsusuario/oauth/access_token",
        );

        // se tiver setado callback id use ele
        $this->callback_param = (isset($oauth['callback_id'])) ? '&callback_id=' . $oauth['callback_id'] : '';
    }

    public function login()
    {
        // Note: do not use "Session" storage in production. Prefer a database
        // storage, such as MySQLi.
        \OAuth1\OAuthStore::instance('Session', $this->options);

        if (empty($_GET["oauth_token"])) {
            //  STEP 1:  If we do not have an OAuth token yet, go get one
            $this->getOauthToken();
        } else {
            //  STEP 2:  Get an access token
            $this->getAccessToken();
            $_SESSION['oauth_user'] = $this->getUserInfo();
            return $_SESSION['oauth_user'];
        }
    }

    // retorna o primeiro vinculo que encontrar com o critério 'campo' == [valor1, ou valor2, ...]
    public function obterVinculo($campo, $valores)
    {
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

    protected function getOauthToken()
    {
        try {
            $tokenResultParams = \OAuth1\OAuthRequester::requestRequestToken($this->options['consumer_key'], null, null, 'POST', null, $this->curl_options);
        } catch (\OAuth1\OAuthException2 $e) {
            echo "OAuthException in (fase 1) requestRequestToken:  " . $e->getMessage();
            var_dump($e);
            exit;
        }
        header("Location: " . $this->options['authorize_uri'] . "?oauth_token=" . $tokenResultParams['token'] . $this->callback_param);
        exit;
    }

    protected function getAccessToken()
    {
        $oauthToken = $_GET['oauth_token'];
        $oauthVerifier = $_GET['oauth_verifier'];
        $tokenResultParams = $_GET;

        try {
            \OAuth1\OAuthRequester::requestAccessToken($this->options['consumer_key'], $oauthToken, 0, 'POST', $_GET, $this->curl_options);
        } catch (\OAuth1\OAuthException2 $e) {
            echo "OAuthException in (fase 2) requestAccessToken:  " . $e->getMessage();
            var_dump($e);
            // Something wrong with the oauth_token.
            // Could be:
            // 1. Was already ok
            // 2. We were not authorized
            exit;
        }
    }

    protected function getUserInfo()
    {
        $request = new \OAuth1\OAuthRequester($this->user_data_url, 'POST');
        $result = $request->doRequest(null, $this->curl_options);
        if ($result['code'] == 200) {
            $loginUSP = json_decode($result['body'], true);
            return $loginUSP;
        } else {
            echo 'Error: result code not 200';
            print_r($result);
            exit;
        }
    }
}
