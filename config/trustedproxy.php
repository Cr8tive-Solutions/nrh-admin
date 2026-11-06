<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | Comma-separated IPs / CIDR ranges whose X-Forwarded-* headers are
    | trusted (read by Laravel's TrustProxies middleware). Defaults to the
    | private ranges: the Caddy reverse proxy reaches this container over the
    | Docker bridge (172.16/12). Never set this to '*' — any client that can
    | reach the container's published port could then spoof X-Forwarded-For
    | (defeating login throttling) and X-Forwarded-Host.
    |
    */

    'proxies' => env('TRUSTED_PROXIES', '10.0.0.0/8,172.16.0.0/12,192.168.0.0/16'),

];
