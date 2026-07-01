<?php

return [
    'base_url' => env(
        'MELHOR_ENVIO_BASE_URL',
        env('MELHOR_ENVIO_ENVIRONMENT', 'sandbox') === 'production'
            ? 'https://www.melhorenvio.com.br'
            : 'https://sandbox.melhorenvio.com.br'
    ),
    'token' => env('MELHOR_ENVIO_TOKEN'),
    'environment' => env('MELHOR_ENVIO_ENVIRONMENT', 'sandbox'),
    'timeout' => (int) env('MELHOR_ENVIO_TIMEOUT', 20),
];
