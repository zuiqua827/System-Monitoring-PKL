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

    /*
    |--------------------------------------------------------------------------
    | SiPintu Gateway (Server-to-Server)
    |--------------------------------------------------------------------------
    |
    | Credentials and base URL for the SiPintu Identity & API Gateway.
    | Credentials are read from .env — never hardcode them.
    |
    | Every request to the Gateway must include the headers:
    |   X-Client-ID, X-Client-Secret, Accept: application/json
    |
    */

    'sipintu' => [
        'api_url' => env('SIPINTU_API_URL', env('SIPINTU_BASE_URL', 'http://localhost:8000')),
        // A Bearer token takes priority when configured. Otherwise the client
        // credentials below are sent as X-Client-ID and X-Client-Secret.
        'api_token' => env('SIPINTU_API_TOKEN'),
        'client_id' => env('SIPINTU_CLIENT_ID'),
        'client_secret' => env('SIPINTU_CLIENT_SECRET'),
        'timeout' => (int) env('SIPINTU_TIMEOUT', 60),
        'connect_timeout' => (int) env('SIPINTU_CONNECT_TIMEOUT', 10),
        // Verify the SSL certificate when calling the SiPintu Gateway.
        // Set SIPINTU_VERIFY_SSL=false in .env for local/development when
        // the server uses a self-signed cert or PHP lacks the CA bundle.
        // Production should keep this true after its CA bundle is configured.
        'verify_ssl' => env('SIPINTU_VERIFY_SSL', true),

        // Downstream & SSO configuration
        'downstream_url' => env('SIPINTU_DOWNSTREAM_URL', env('APP_URL', 'https://simongan.smkn1bangsri.sch.id')),
        'sso_callback_url' => env('SIPINTU_SSO_CALLBACK_URL', env('SIPINTU_REDIRECT_URI')),
        'webhook_secret' => env('SIPINTU_WEBHOOK_SECRET'),
    ],

];
