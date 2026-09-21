<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create(['name' => 'Alex Rivera']);
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk()
        ->assertSee('Welcome back, Alex Rivera!')
        ->assertSee('No notifications yet.')
        ->assertSee('data-test="notification-bell"', false);
});

test('the dashboard escapes the user name in its welcome message', function () {
    $user = User::factory()->create(['name' => 'Alex <script>alert(1)</script>']);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Alex &lt;script&gt;alert(1)&lt;/script&gt;', false)
        ->assertDontSee('<script>alert(1)</script>', false);
});
