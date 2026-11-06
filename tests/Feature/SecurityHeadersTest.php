<?php

use Illuminate\Support\Facades\Route;

/**
 * Global response hardening (SecurityHeaders) and the trusted-proxy list.
 */
beforeEach(function () {
    Route::get('/_test/powered-by', fn () => response('ok')->header('X-Powered-By', 'PHP/8.4'));
    Route::get('/_test/client-ip', fn () => response(request()->ip()));
});

it('strips the X-Powered-By header', function () {
    $this->get('/_test/powered-by')
        ->assertOk()
        ->assertHeaderMissing('X-Powered-By')
        ->assertHeader('X-Frame-Options', 'DENY');
});

it('trusts X-Forwarded-For only from the private proxy ranges by default', function () {
    expect(config('trustedproxy.proxies'))->toBe('10.0.0.0/8,172.16.0.0/12,192.168.0.0/16');

    $this->withServerVariables(['REMOTE_ADDR' => '172.18.0.5'])
        ->withHeader('X-Forwarded-For', '203.0.113.7')
        ->get('/_test/client-ip')
        ->assertSeeText('203.0.113.7');

    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.9'])
        ->withHeader('X-Forwarded-For', '203.0.113.7')
        ->get('/_test/client-ip')
        ->assertSeeText('198.51.100.9');
});

it('reads the trusted proxy list from config', function () {
    config(['trustedproxy.proxies' => '198.51.100.9']);

    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.9'])
        ->withHeader('X-Forwarded-For', '203.0.113.7')
        ->get('/_test/client-ip')
        ->assertSeeText('203.0.113.7');

    $this->withServerVariables(['REMOTE_ADDR' => '172.18.0.5'])
        ->withHeader('X-Forwarded-For', '203.0.113.7')
        ->get('/_test/client-ip')
        ->assertSeeText('172.18.0.5');
});
