<?php

use App\Models\User;
use App\UserRole;
use App\UserStatus;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

test('login shows one local demo button per role without a registration link', function () {
    $response = $this->get(route('login'));

    foreach (UserRole::cases() as $role) {
        $response->assertSee(route('demo.login', $role->value), false);
    }

    $response->assertDontSee('Sign up');
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

    $this->get(route('login'))->assertDontSee('Demo accounts');
    $this->withoutMiddleware(PreventRequestForgery::class);
    $this->post(route('demo.login', UserRole::Admin->value))->assertNotFound();
    $this->assertGuest();
});
