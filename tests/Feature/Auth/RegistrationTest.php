<?php

test('public registration is unavailable', function () {
    $this->get('/register')->assertNotFound();
});

test('guests cannot create an account through the old registration endpoint', function () {
    $this->post('/register', [
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();

    $this->assertGuest();
});
