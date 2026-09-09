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

    'resellportal' => [
        'base_url' => env('RESELLPORTAL_BASE_URL', 'https://panel.resellportal.com/wp-json/resellportal/v1/'),
        'api_key' => env('RESELLPORTAL_API_KEY'),
        'api_secret' => env('RESELLPORTAL_API_SECRET'),
        'timeout' => (int) env('RESELLPORTAL_TIMEOUT', 15),
        'balance_cache_ttl' => (int) env('RESELLPORTAL_BALANCE_CACHE_TTL', 60),
        'live_mode' => filter_var(env('LIVEMODE', true), FILTER_VALIDATE_BOOLEAN),
    ],

];
