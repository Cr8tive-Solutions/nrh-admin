<?php

// The admin portal has no public landing page — "/" redirects to the dashboard,
// which in turn bounces guests to the login screen.
it('redirects an unauthenticated visitor to login', function () {
    $this->get('/')->assertRedirect();

    $this->followingRedirects()->get('/')->assertOk()->assertSee('login', false);
});
