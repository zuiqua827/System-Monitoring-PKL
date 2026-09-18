<?php

it('redirects root to login page', function () {
    $response = $this->get('/');

    $response->assertRedirect('/login');
});
