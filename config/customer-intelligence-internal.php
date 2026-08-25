<?php

/*
|--------------------------------------------------------------------------
| Customer Intelligence — modulo interno
|--------------------------------------------------------------------------
|
| Configuracao do modulo NATIVO do projeto (app/CustomerIntelligence).
|
| Nao confundir com config/customer-intelligence.php, que pertence ao SDK
| externo e continua ativo enquanto a migracao nao termina. Os dois coexistem
| de proposito; o arquivo do SDK sai na fase CI-08.
|
*/

return [

    /*
    | Liga ou desliga a coleta interna. Com false, o middleware nao grava nada
    | e nao emite cookies — util para diagnosticar impacto em producao sem
    | precisar remover o middleware.
    */
    'enabled' => env('CI_ENABLED', true),

    /*
    | Cookies de identificacao.
    |
    | Os nomes sao mantidos identicos aos que o SDK externo ja emite (decisao 3
    | da auditoria CI-01): renomea-los zeraria a identidade de todos os
    | visitantes ja conhecidos, cujos cookies tem validade de dois anos. O
    | prefixo `jmf_ci_` passa a ser um nome historico, sem vinculo com o
    | servico externo.
    |
    | Os TTLs tambem espelham os do SDK, para que os dois middlewares nao
    | disputem a validade do mesmo cookie durante a coexistencia.
    */
    'visitor_cookie' => [
        'name' => env('CI_VISITOR_COOKIE_NAME', 'jmf_ci_visitor_id'),
        'minutes' => (int) env('CI_VISITOR_COOKIE_MINUTES', 60 * 24 * 365 * 2),
    ],

    'session_cookie' => [
        'name' => env('CI_SESSION_COOKIE_NAME', 'jmf_ci_session_id'),
        'minutes' => (int) env('CI_SESSION_COOKIE_MINUTES', 30),
    ],

    /*
    | Fila de gravacao dos eventos.
    |
    | `connection` vazio usa a conexao padrao da aplicacao (QUEUE_CONNECTION).
    |
    | A fila e propria, e nao a `default`, porque rastreamento e o trabalho
    | menos urgente do sistema: o worker atende `default` e `email-marketing`
    | primeiro, entao um pico de navegacao nunca atrasa um e-mail de pedido.
    | O worker do compose.yaml precisa lista-la em `--queue`.
    */
    'queue' => [
        'connection' => env('CI_QUEUE_CONNECTION'),
        'name' => env('CI_QUEUE', 'customer-intelligence'),
    ],

];
