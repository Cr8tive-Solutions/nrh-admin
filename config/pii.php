<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PII encryption key (SHARED across both portals)
    |--------------------------------------------------------------------------
    |
    | Sensitive columns (candidate identity_number) are encrypted at rest with
    | this key rather than APP_KEY, because the admin and client portals share
    | one database but each has its OWN APP_KEY — a value encrypted by one app
    | must be decryptable by the other. Set the SAME PII_KEY in BOTH apps.
    |
    | Format: "base64:<32 url-safe bytes>", e.g. the output of
    |   php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
    |
    | When empty, encryption is OFF: values are stored as plaintext and the
    | blind index is not populated (so behaviour is unchanged). This lets the
    | code deploy with no effect, then be activated by setting PII_KEY in both
    | apps and running `php artisan pii:backfill-identity`. Once set, NEVER
    | change it — existing ciphertext and hashes would become unreadable.
    |
    */

    'key' => env('PII_KEY', ''),

];
