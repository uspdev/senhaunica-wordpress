# Senhaunica
Biblioteca genérica para integrar senha única em PHP

## Dependência

biblioteca zorrodg/oauth-php  
biblioteca ext-curl

## Instalação

Se seu projeto não usa composer ainda, é uma boa idéia começar a usá-lo.

```
composer init
composer require uspdev/senhaunica
composer install
```

## Uso

Esta biblioteca foi testada em debian 10, debian 9, ubuntu 18.04 e ubuntu 16.04.

O token pode ser usado para várias aplicações por meio do callback_id cadastrado em https://dev.uspdigital.usp.br/adminws/

Deve-se criar uma rota (/loginusp por exemplo) com o seguinte código:

```php
require_once '../vendor/autoload.php';

$auth = new Uspdev\Senhaunica\Senhaunica([
    'consumer_key' => 'aaaa',
    'consumer_secret' => 'sdkjfcsdkfhsdkfhsdkfhsdhkf',
    'callback_id' => 1, // callback_id é o sequencial no servidor
    'amb' => 'dev',// 'dev' = teste, 'prod' = producao
]);

$res = $auth->login();

echo '<pre>';
print_r($res);
echo '</pre>';

header('Location:/alguma_rota');

```

Se você quiser, por exemplo, validar o vínculo do login, pode o código abaixo. Ele irá retornar o primeiro vínculo que encontrar dentro da lista fornecida. Ao invés de usar `tipoVinculo` você pode usar qualquer variável dentro do array de vínculos.

```php
$vinculo = $auth->obterVinculo('tipoVinculo', ['SERVIDOR','OUTRO_VINCULO', '...']);

```


OBS: Anteriormente o ambiente era setado como 1=dev e 2=prod. Nas novas versões os números 1 e 2 serão descontinuados, premanecendo somente 'dev' e 'prod'.