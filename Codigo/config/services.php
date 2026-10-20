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

    'whatsapp' => [
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
        'app_secret' => env('WHATSAPP_APP_SECRET'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'modo_simulacion' => env('WHATSAPP_MODO_SIMULACION', false),
        'graph_api_version' => env('WHATSAPP_GRAPH_API_VERSION', 'v25.0'),
        'ca_bundle' => env('WHATSAPP_CA_BUNDLE'),
        'destinatarios_permitidos' => env('WHATSAPP_DESTINATARIOS_PERMITIDOS'),
        'validar_firma' => env('WHATSAPP_VALIDAR_FIRMA', true),
        'media_disk' => env('WHATSAPP_MEDIA_DISK', 'local'),
        'media_max_kb' => (int) env('WHATSAPP_MEDIA_MAX_KB', 10240),
        'plantilla_vencimiento' => env('WHATSAPP_TEMPLATE_VENCIMIENTO', 'recordatorio_vencimiento'),
        'plantilla_vencida' => env('WHATSAPP_TEMPLATE_VENCIDA', 'recordatorio_cuota_vencida'),
        'idioma_plantilla' => env('WHATSAPP_TEMPLATE_LANGUAGE', 'es_AR'),
        'recordatorios_habilitados' => env('WHATSAPP_RECORDATORIOS_HABILITADOS', false),
        'recordatorios_destinatarios' => env('WHATSAPP_RECORDATORIOS_DESTINATARIOS', ''),
        'control_consumo_habilitado' => env('WHATSAPP_CONTROL_CONSUMO_HABILITADO', true),
        'limite_servicios_mes' => (int) env('WHATSAPP_LIMITE_SERVICIOS_MES', 900),
        'plantillas_reales_habilitadas' => env('WHATSAPP_PLANTILLAS_REALES_HABILITADAS', false),
        'plantillas_destinatarios' => env('WHATSAPP_PLANTILLAS_DESTINATARIOS', ''),
        'limite_plantillas_mes' => (int) env('WHATSAPP_LIMITE_PLANTILLAS_MES', 4),
        'limite_plantillas_dia' => (int) env('WHATSAPP_LIMITE_PLANTILLAS_DIA', 2),
    ],

    'comprobantes' => [
        'notificar_resultado' => env('COMPROBANTES_NOTIFICAR_RESULTADO', false),
        'plantillas_fuera_ventana' => env('COMPROBANTES_PLANTILLAS_FUERA_VENTANA', false),
        'plantilla_aprobado' => env('WHATSAPP_TEMPLATE_PAGO_APROBADO', 'demo_pago_aprobado_v1'),
        'plantilla_rechazado' => env('WHATSAPP_TEMPLATE_PAGO_RECHAZADO', 'demo_pago_rechazado_v1'),
    ],

    'tickets' => [
        'notificar_resultado' => env('TICKETS_NOTIFICAR_RESULTADO', false),
    ],

    'transferencias' => [
        'habilitadas' => env('TRANSFERENCIAS_HABILITADAS', false),
        'id_cuenta' => (int) env('TRANSFERENCIAS_ID_CUENTA', 0),
    ],

    'ocr' => [
        'habilitado' => env('OCR_HABILITADO', false),
        'tesseract_bin' => env('TESSERACT_BIN', 'tesseract'),
        'pdftoppm_bin' => env('PDFTOPPM_BIN', 'pdftoppm'),
        'timeout' => (int) env('OCR_TIMEOUT', 25),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'timeout' => env('OPENAI_TIMEOUT', 15),
        'confianza_minima' => env('IA_CONFIANZA_MINIMA', 0.65),
    ],

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

];
