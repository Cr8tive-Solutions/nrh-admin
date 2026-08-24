<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Hashids salt
    |--------------------------------------------------------------------------
    |
    | Salt for the URL-id obfuscation (hid()/hdecode(), HasHashid route
    | binding). Falls back to APP_KEY when unset — but set HASHIDS_SALT in
    | production so rotating the encryption key does not invalidate every
    | bookmarked/emailed URL. Once set, never change it (same reason).
    | Each portal has its own salt; hashids are deliberately app-local.
    |
    */

    'salt' => env('HASHIDS_SALT', ''),

    'min_length' => 8,

];
