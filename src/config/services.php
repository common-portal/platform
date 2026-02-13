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

    'walletids' => [
        'base_url' => env('WALLETIDS_BASE_URL', 'https://walletids.net/api'),
        'api_key' => env('WALLETIDS_API_KEY', ''),
        'webhook_secret' => env('WALLETIDS_WEBHOOK_SECRET', ''),
    ],

    'solana' => [
        'rpc_url' => env('SOLANA_RPC_URL', 'https://api.mainnet-beta.solana.com'),
    ],

    'xai' => [
        'api_key' => env('XAI_API_KEY'),
        'model' => env('XAI_MODEL', 'grok-4-1-fast-reasoning'),
        'base_url' => env('XAI_BASE_URL', 'https://api.x.ai/v1'),
    ],

    'shfinancial' => [
        'api_url' => env('SHF_API_URL', 'https://api.sh-payments.com'),
        'client_id' => env('SHF_CLIENT_ID'),
        'client_secret' => env('SHF_CLIENT_SECRET'),
        'scope' => env('SHF_SCOPE', 'apiv1.programme'),
        'creditor_id' => env('SHF_CREDITOR_ID', ''),
    ],

];
