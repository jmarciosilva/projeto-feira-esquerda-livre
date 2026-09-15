<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    | Catalog Intelligence — trava técnica do provider externo (CAT-10A.1).
    |
    | A configuração operacional do provider (ligado, provider, modelo, chave e
    | prazo) é do banco, gravada pelo painel em Admin → Configurações →
    | Inteligência Artificial, e lida por App\Services\CatalogAi\CatalogAiSettings.
    | Daqui só sai a trava: verdadeira, nenhuma chamada externa acontece, seja
    | qual for o banco. Ela nunca liga o provider. O phpunit.xml a força.
    */
    'catalog_ai' => [
        'force_disabled' => env('CATALOG_AI_FORCE_DISABLED', false),
    ],

];
