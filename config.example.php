<?php 
// credenciais do oauth
// Callback para cadastro: /?rest_route=/senhaunica/login 
$clientCredentials = [
    'identifier' =>'sua chave',
    'secret' => 'o secret da chave',
    'callback_id' => 34,
];

// usuários que farão login como administrator do WP
// os usuários definidos na constante SENHAUNICA_ADMINS também serão autorizados
//   (util para definir admins por CLI - wp config set SENHAUNICA_ADMINS 123,456)
// caso o usuário não esteja aqui, ele será autenticado mas não fará login no WP
$senhaunicaAdmins = [
    // 12345,
    // 67890,
];

// para onde vai redirecionar depois de efetuado o login
$redirect = '/';

// Funções a serem chamadas pelo plugin. Não tem como passar parâmetro.
$callbacks = [
//    'senhaunica_demo',
];
