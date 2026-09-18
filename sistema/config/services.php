<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    'whatsapp' => [
        'modo_simulacion' => env('WHATSAPP_MODO_SIMULACION', env('APP_ENV', 'production') === 'local'),
        'descargar_archivos' => env('WHATSAPP_DESCARGAR_ARCHIVOS', true),
        'tamano_maximo_archivo_kb' => env('WHATSAPP_TAMANO_MAXIMO_ARCHIVO_KB', 10240),
        'url_base' => env('WHATSAPP_URL_BASE', 'https://graph.facebook.com'),
        'token_verificacion' => env('WHATSAPP_TOKEN_VERIFICACION'),
        'secreto_aplicacion' => env('WHATSAPP_SECRETO_APLICACION'),
        'token_acceso' => env('WHATSAPP_TOKEN_ACCESO'),
        'id_numero_telefono' => env('WHATSAPP_ID_NUMERO_TELEFONO'),
        'version_api' => env('WHATSAPP_VERSION_API', 'v23.0'),
    ],

    'gemini' => [
        'url_base' => env('GEMINI_URL_BASE', 'https://generativelanguage.googleapis.com'),
        'clave_api' => env('GEMINI_API_KEY'),
        'modelo' => env('GEMINI_MODEL', 'gemini-3.5-flash-lite'),
        'umbral_confianza' => env('GEMINI_UMBRAL_CONFIANZA', 0.65),
    ],

];
