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
    | Catalog Intelligence — provider externo (CAT-10A). Desligado por padrão.
    |
    | Lido por App\Services\CatalogAi\CatalogAiProviderSelector: valor ausente ou
    | inválido resolve o contrato para o NullCatalogAiProvider. O prazo nunca passa
    | de 8 segundos. A chave nunca é versionada.
    */
    'catalog_ai' => [
        'enabled' => env('CATALOG_AI_ENABLED', false),
        'provider' => env('CATALOG_AI_PROVIDER'),
        'model' => env('CATALOG_AI_MODEL'),
        'api_key' => env('CATALOG_AI_API_KEY'),
        'timeout' => env('CATALOG_AI_TIMEOUT', 8),
    ],

];
