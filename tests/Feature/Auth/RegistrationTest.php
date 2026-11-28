<?php

test('registration screen cannot be rendered when registration is disabled', function () {
    $response = $this->get('/register');

    $response->assertStatus(404);
});

test('public registration is disabled and does not register users', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertStatus(404);
    $this->assertGuest();
});
