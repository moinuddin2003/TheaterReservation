<?php

it('redirects visitors to the login page', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('login'));
});
