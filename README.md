# Plugin senhaunica-wordpress

Plugin wordpress para senha única USP

Callback para cadastro: /?rest_route=/senhaunica/login 

https://www.youtube.com/watch?v=Di7wjDGpFAg&t=163s

## Updates

**9/5/2022 - versão 2.0.2**

* atualizado para senhaunica 2.0 que agora usa league/oauth1-client
* compatível com php 8 e WP 6.2
* alterado o arquivo de configuração com novas opções

## Utilização

O pacote atual, que inclui o `vendor/` está empacotado para PHP 8.2. Caso seu PHP seja de versão anterior, pode ser necessário reeempacotar a pasta `vendor/`

* copie todo o source para a pasta plugins do wordpress
* copie o arquivo config.example.php para config.php e ajuste as variáveis
* ative o plugin

### Exemplo

Esta função está associada à um shortcode `senhaunica_demo`. Ao carregar em uma página, caso não tenha feito login ainda, ele direcionará para o login USP e retornará na mesma página que o chamou e mostrará o retorno do oauth.

O botão de logout serve para limpar os dados de oauth, e não para deslogar do WP.

```php
/**
 * Função para demonstrar o uso
 */
function senhaunica_demo()
{
    $usuario = UspdevSenhaunicaWP::obterUsuario();

    $ret = '';
    $ret .= '<a href="../?rest_route=/senhaunica/logout">Logout</a>';
    $ret .= '<pre>';
    $ret .= json_encode($usuario, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $ret .= '</pre>';
    return $ret;
}
add_shortcode('senhaunica_demo', 'senhaunica_demo');
``` 

## Plugins de interesse
**Session plugin**

* https://wordpress.org/plugins/wp-native-php-sessions/#why%20store%20them%20in%20the%20database%3F

* This plugin implements PHP’s native session handlers, backed by the WordPress database.