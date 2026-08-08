<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Ativação do SDK
    |--------------------------------------------------------------------------
    |
    | Se false, o SDK não envia dados (útil para ambientes de teste).
    | Padrão: true
    |
    */
    'enabled' => env('JMF_CI_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | URL base da API
    |--------------------------------------------------------------------------
    |
    | URL da plataforma JMF Customer Intelligence.
    | Exemplo: https://ci.jmfsystem.com/api/v1
    | Obrigatório quando JMF_CI_ENABLED=true
    |
    */
    'base_url' => env('JMF_CI_BASE_URL'),

    /*
    |--------------------------------------------------------------------------
    | Token da aplicação
    |--------------------------------------------------------------------------
    |
    | Token de autenticação Sanctum gerado no painel admin da plataforma
    | (Tenants > Applications > Tokens).
    | Obrigatório quando JMF_CI_ENABLED=true
    |
    */
    'token' => env('JMF_CI_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Timeout de requisições HTTP (em segundos)
    |--------------------------------------------------------------------------
    |
    | Tempo máximo que o SDK espera por uma resposta da API.
    | Padrão: 5 segundos
    |
    */
    'timeout' => (int) env('JMF_CI_TIMEOUT', 5),

    /*
    |--------------------------------------------------------------------------
    | Número de tentativas de envio
    |--------------------------------------------------------------------------
    |
    | Quantas vezes o SDK tentará enviar dados antes de desistir.
    | Padrão: 3 tentativas
    |
    */
    'tries' => (int) env('JMF_CI_TRIES', 3),

    /*
    |--------------------------------------------------------------------------
    | Backoff para retry (em segundos)
    |--------------------------------------------------------------------------
    |
    | Intervalo de espera entre tentativas de envio.
    | Padrão: [5, 30, 120] segundos (exponencial)
    |
    */
    'backoff' => [5, 30, 120],

    /*
    |--------------------------------------------------------------------------
    | Fila de processamento
    |--------------------------------------------------------------------------
    |
    | Configuração da fila para processamento assíncrono.
    | Se queue_connection estiver vazio, usa a conexão padrão da app.
    |
    */
    'queue_connection' => env('JMF_CI_QUEUE_CONNECTION'),
    'queue' => env('JMF_CI_QUEUE'),

    /*
    |--------------------------------------------------------------------------
    | Modo síncrono
    |--------------------------------------------------------------------------
    |
    | Se true, envia dados de forma síncrona (aguarda resposta).
    | Se false (padrão), envia de forma assíncrona via fila.
    | Útil para desenvolvimento/testes.
    |
    */
    'sync' => env('JMF_CI_SYNC', false),

    /*
    |--------------------------------------------------------------------------
    | Cookies de identificação
    |--------------------------------------------------------------------------
    |
    | visitor_id: identificador anônimo de longa duração (~2 anos).
    | session_id: identificador de sessão rolante (30 min).
    | Ambos são independentes da autenticação do Laravel.
    |
    */
    'visitor_cookie' => [
        'name' => env('JMF_CI_VISITOR_COOKIE_NAME', 'jmf_ci_visitor_id'),
        'minutes' => (int) env('JMF_CI_VISITOR_COOKIE_MINUTES', 60 * 24 * 365 * 2),
    ],

    'session_cookie' => [
        'name' => env('JMF_CI_SESSION_COOKIE_NAME', 'jmf_ci_session_id'),
        'minutes' => (int) env('JMF_CI_SESSION_COOKIE_MINUTES', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Validação automática de configuração
    |--------------------------------------------------------------------------
    |
    | Se true, valida as configurações obrigatórias no register do
    | ServiceProvider. Se false, deixa a validação para o primeiro uso.
    | Padrão: true
    |
    */
    'validate_on_boot' => env('JMF_CI_VALIDATE_ON_BOOT', true),
];
