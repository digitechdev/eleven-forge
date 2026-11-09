<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Inertia\Testing\AssertableInertia as Assert;

test('login offers one local demo sign-in per role', function () {
    $this->get(route('login'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/Login')
            ->where('demoAccounts', array_map(fn (UserRole $role): array => [
                'label' => $role->label(),
                'url' => route('demo.login', $role->value),
            ], UserRole::cases())),
        );
});

test('each demo button signs in the matching role', function (UserRole $role) {
    $this->seed(DatabaseSeeder::class);
    $user = User::query()->where('email', strtolower($role->value).'@elevenforge.test')->firstOrFail();

    $this->post(route('demo.login', $role->value))
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
    expect($user->hasRole($role->value))->toBeTrue();
})->with(UserRole::cases());

test('a missing or inactive demo account cannot sign in', function () {
    $this->post(route('demo.login', UserRole::Admin->value))->assertNotFound();

    $this->seed(DatabaseSeeder::class);
    $admin = User::query()->where('email', 'admin@elevenforge.test')->firstOrFail();
    $admin->forceFill(['status' => UserStatus::Inactive])->save();

    $this->post(route('demo.login', UserRole::Admin->value))->assertForbidden();
    $this->assertGuest();
});

test('demo sign in is unavailable outside local and testing environments', function () {
    $this->seed(DatabaseSeeder::class);
    app()->detectEnvironment(fn (): string => 'production');

    $this->get(route('login'))
        ->assertInertia(fn (Assert $page) => $page->where('demoAccounts', []));
    $this->withoutMiddleware(PreventRequestForgery::class);
    $this->post(route('demo.login', UserRole::Admin->value))->assertNotFound();
    $this->assertGuest();
});
