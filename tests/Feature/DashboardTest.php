<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create(['name' => 'Alex Rivera']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('auth.user.name', 'Alex Rivera'),
        );
});

test('the dashboard does not render the user name as raw html', function () {
    $user = User::factory()->create(['name' => 'Alex <script>alert(1)</script>']);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('<script>alert(1)</script>', false);
});
