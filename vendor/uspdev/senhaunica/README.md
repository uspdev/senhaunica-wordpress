# Senhaunica
Biblioteca genérica para integrar senha única em PHP

## Changelog

3/5/2023: versão 2.0
* nova versão agora usa a biblioteca league/oauth1-client
* compatível com php 8
* Chamada agora é estática

## Dependência

* biblioteca league/oauth1-client
* PHP >=7.1 | >=8.0
## Instalação

Se seu projeto não usa composer ainda, é uma boa idéia começar a usá-lo.

```
composer require uspdev/senhaunica:2.0
```

## Uso

Esta biblioteca foi testada em debian 10 e ubuntu 22.04.

O token pode ser usado para várias aplicações por meio do callback_id cadastrado em https://uspdigital.usp.br/adminws/oauthConsumidorAcessar

Deve-se criar uma rota (/loginusp por exemplo) com o seguinte código:

```php
require_once __DIR__.'/vendor/autoload.php';
session_start();

use Uspdev\Senhaunica\Senhaunica;

$clientCredentials = [
    'identifier' => 'identificacao',
    'secret' => 'chave-secreta',
    'callback_id' => 0,
];

Senhaunica::login($clientCredentials);

header('Location:../');
exit;
```

Opcionalmente você pode passar os parâmetros via `env`:

```php
require_once __DIR__.'/vendor/autoload.php';
session_start();

use Uspdev\Senhaunica\Senhaunica;

putenv('SENHAUNICA_KEY=');
putenv('SENHAUNICA_SECRET=');
putenv('SENHAUNICA_CALLBACK_ID=');

Senhaunica::login();

header('Location:../');
exit;
```

Os dados do usuário autenticado estarão em `$_SESSION['oauth_user']`

Ele também pode ser recuperado usando

    $user = Uspdev\Senhaunica\Senhaunica::getUserDetail();

Ele contém um array com todos os dados retornados do oauth. Exemplo:

    [loginUsuario] => 111111
    [nomeUsuario] => Jose Maria da Silva
    [tipoUsuario] => I
    [emailPrincipalUsuario] => email@usp.br
    [emailAlternativoUsuario] => email-alternativo@gmail.com
    [emailUspUsuario] => outro-email@usp.br
    [numeroTelefoneFormatado] => (0xx16)1234-5678 - ramal USP: 345678
    [wsuserid] => Iasdkughacsdghçalekhagsghaegawe
    [vinculo] => Array
        (
            [0] => Array
                (
                    [tipoVinculo] => SERVIDOR
                    [codigoSetor] => 000
                    [nomeAbreviadoSetor] => ABC
                    [nomeSetor] => Meu setor
                    [codigoUnidade] => 18
                    [siglaUnidade] => EESC
                    [nomeUnidade] => Escola de Engenharia de São Carlos
                    [nomeVinculo] => Servidor
                    [nomeAbreviadoFuncao] => Minha função
                    [tipoFuncao] => Informática
                )

        )

Adicionalmente, se você quiser validar o vínculo do login, use o código abaixo. Ele irá retornar o primeiro vínculo que encontrar dentro da lista fornecida. Ao invés de usar `tipoVinculo` você pode usar qualquer variável dentro do array de vínculos.

```php
$campo = 'tipoVinculo';
$valores = ['SERVIDOR','OUTRO_VINCULO', '...'];
$vinculo = Uspdev\Senhaunica\Senhaunica::obterVinculo($campo, $valores);
```
