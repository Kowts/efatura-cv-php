<?php

declare(strict_types=1);

/*
 * Configuração publicável para Laravel.
 *
 * Em PHP puro, Symfony ou Yii2, prefira construir EfaturaConfig directamente
 * ou reutilizar EfaturaConfig::fromArray(). Este ficheiro assume que o helper
 * env() do Laravel está disponível durante o carregamento da configuração.
 */

return [
    'transmitter_nif' => env('EFATURA_TRANSMITTER_NIF'),
    'transmitter_led' => env('EFATURA_TRANSMITTER_LED', '001'),
    'transmitter_key' => env('EFATURA_TRANSMITTER_KEY'),
    'default_serie' => env('EFATURA_DEFAULT_SERIE', 'SER-F'),
    'software_code' => env('EFATURA_SOFTWARE_CODE'),
    'software_name' => env('EFATURA_SOFTWARE_NAME'),
    'software_version' => env('EFATURA_SOFTWARE_VERSION', '1.0.0'),
    'middleware_base_url' => env('EFATURA_MIDDLEWARE_URL'),
    'middleware_dfe_path' => env('EFATURA_MIDDLEWARE_DFE_PATH', '/v1/dfe'),
    'middleware_event_path' => env('EFATURA_MIDDLEWARE_EVENT_PATH', '/v1/event'),
    'platform_base_url' => env('EFATURA_PLATFORM_URL', 'https://services.efatura.cv'),
    'platform_dfe_path' => env('EFATURA_PLATFORM_DFE_PATH', '/v1/dfe'),
    'platform_event_path' => env('EFATURA_PLATFORM_EVENT_PATH', '/v1/event'),
    'dfa_base_url' => env('EFATURA_DFA_URL', 'https://pe.efatura.cv/dfe/view'),
    'environment' => env('EFATURA_ENVIRONMENT', 'TEST'),
    'emitter' => [
        'taxId' => [
            'countryCode' => 'CV',
            'value' => env('EFATURA_EMITTER_NIF'),
        ],
        'name' => env('EFATURA_EMITTER_NAME'),
        'address' => [
            'countryCode' => 'CV',
            'addressDetail' => env('EFATURA_EMITTER_ADDRESS'),
        ],
        'contacts' => [
            'email' => env('EFATURA_EMITTER_EMAIL'),
            'telephone' => env('EFATURA_EMITTER_PHONE'),
        ],
    ],
];
