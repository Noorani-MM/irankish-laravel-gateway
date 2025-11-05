<?php

return [
    // Credentials
    'terminal_id'   => env('IRANKISH_TERMINAL_ID', ''),
    'password'      => env('IRANKISH_PASSWORD', ''),
    'acceptor_id'   => env('IRANKISH_ACCEPTOR_ID', ''),
    'public_key'    => env('IRANKISH_PUBLIC_KEY', ''), // PEM content or absolute file path

    // Callback URL (your controller route)
    'callback_url'  => env('IRANKISH_CALLBACK_URL', ''),

    // Endpoints (override in .env if IKC changes)
    'endpoints' => [
        'make_token'   => env('IRANKISH_MAKE_TOKEN_URL', 'https://ikc.shaparak.ir/api/v3/tokenization/make'),
        'confirm'      => env('IRANKISH_CONFIRM_URL',   'https://ikc.shaparak.ir/api/v3/confirmation/purchase'),
        // base URL to redirect user to the gateway with ?token=...
        'payment_base' => env('IRANKISH_PAYMENT_URL',   'https://ikc.shaparak.ir/TPayment/Payment/Index'),
        // اگر مستندات شما مسیر iuiv3/IPG/Index را الزام می‌کند، مقدار بالا را در .env عوض کنید.
    ],

    // HTTP options
    'http' => [
        'timeout'          => 20,
        'connect_timeout'  => 10,
        'verify_ssl'       => true,
    ],
];
