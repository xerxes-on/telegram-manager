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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    'paycom' => [
        'url' => env('PAYME_API_URL'),
        'id' => env('PAYME_API_ID'),
        'key' => env('PAYME_API_KEY'),
    ],
    'tax' => [
        'product_code' => env('TAX_PRODUCT_CODE', '10306013001000000'),
        'package_code' => env('TAX_PACKAGE_CODE', 'package_code'),
        'vat_percent' => env('TAX_VAT_PERCENT', 4),
    ],
    
    'payment' => [
        'max_retries' => env('MAX_PAYMENT_RETRIES', 3),
        'retry_interval_hours' => env('PAYMENT_RETRY_INTERVAL_HOURS', 24),
    ],

];
