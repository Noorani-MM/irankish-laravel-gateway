<?php

return [
    // Core credentials (provided by IranKish)
    'terminal_id' => env('IRANKISH_TERMINAL_ID'),
    'password'    => env('IRANKISH_PASSWORD'),        // Pass Phrase (16 chars)
    'acceptor_id' => env('IRANKISH_ACCEPTOR_ID'),
    'public_key'  => env('IRANKISH_PUBLIC_KEY'),       // PEM public key

    // Default callback URL (revertUri in tokenization)
    'revert_url'  => env('IRANKISH_REVERT_URL', url('/irankish/callback')),

    // Optional override for testing
    'base_url'    => env('IRANKISH_BASE_URL', null),
];
