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
    |--------------------------------------------------------------------------
    | Stock Market Data APIs
    |--------------------------------------------------------------------------
    |
    | Alpha Vantage API for stock fundamental data
    |
    | Free tier: 5 calls/min = 7,200/day (more than enough with caching)
    | Get free API key: https://www.alphavantage.co/
    |
    | Supports: Global stocks and indices
    | Fundamental data: EPS, BookValue, ROE, DER, ProfitMargin
    |
    */

    'alpha_vantage' => [
        'key' => env('ALPHA_VANTAGE_KEY', 'demo'),
        'base_url' => 'https://www.alphavantage.co',
        'timeout' => 15,
    ],
];
