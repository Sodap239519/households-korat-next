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

    // LINE Messaging API — Official Account กลาง 1 ตัว (LINE Notify ถูกปิดบริการแล้ว)
    // แต่ละ seller_group เก็บปลายทาง (line_target_id) ของตัวเอง
    'line' => [
        'channel_access_token' => env('LINE_CHANNEL_ACCESS_TOKEN'),
        'channel_secret'       => env('LINE_CHANNEL_SECRET'),
        'push_url'             => env('LINE_PUSH_URL', 'https://api.line.me/v2/bot/message/push'),
    ],

    // บริการตรวจสลิปโอนเงินอัตโนมัติ (เช่น SlipOK / Slip2Go) — เว้นว่าง = ตรวจด้วยมือ
    'slip' => [
        'provider' => env('SLIP_VERIFY_PROVIDER'),       // เช่น 'slipok'
        'token'    => env('SLIP_VERIFY_TOKEN'),
        'url'      => env('SLIP_VERIFY_URL'),
    ],

];
