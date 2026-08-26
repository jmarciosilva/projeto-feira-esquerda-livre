<?php

/*
|--------------------------------------------------------------------------
| Customer Intelligence
|--------------------------------------------------------------------------
|
| Configuracao do modulo de inteligencia de cliente (app/CustomerIntelligence).
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
    | O prefixo `jmf_ci_` e historico: veio da integracao externa que deu origem
    | ao modulo. Renomear zeraria a identidade de todos os visitantes ja
    | conhecidos, cujos cookies valem dois anos — por isso os nomes ficaram.
    | Hoje nao ha nenhuma dependencia externa por tras deles.
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

    /*
    | Retencao.
    |
    | Eventos brutos vivem 180 dias; os agregados de `ci_daily_metrics` sao
    | permanentes. E o agregado que torna o expurgo viavel — o painel le dele,
    | entao apagar o evento bruto nao apaga a serie historica.
    |
    | O expurgo roda pelo comando customer-intelligence:prune-events, agendado
    | diariamente em routes/console.php.
    */
    'retention' => [
        'event_days' => (int) env('CI_RETENTION_DAYS', 180),
    ],

];
