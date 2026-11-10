<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Innovation;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

/**
 * @return array<string, mixed>
 */
function userPayload(array $overrides = []): array
{
    return [
        'name' => 'Jamie Cruz',
        'email' => 'jamie@example.com',
        'password' => 'secret-password-123',
        'password_confirmation' => 'secret-password-123',
        'role' => UserRole::Contributor->value,
        'status' => UserStatus::Active->value,
        ...$overrides,
    ];
}

test('viewers cannot open or change user management', function () {
    $viewer = User::factory()->viewer()->create();
    $other = User::factory()->viewer()->create();

    $this->actingAs($viewer);

    $this->get(route('administration.users.index'))->assertForbidden();
    $this->post(route('administration.users.store'), userPayload())->assertForbidden();
    $this->put(route('administration.users.update', $other), userPayload())->assertForbidden();
    $this->patch(route('administration.users.status', $other))->assertForbidden();
});

test('admins can list and filter users with their role, status, and innovation count', function () {
    $admin = User::factory()->admin()->create(['name' => 'Admin Person']);
    $contributor = User::factory()->contributor()->create(['name' => 'Casey Builder']);
    Innovation::factory()->count(2)->for($contributor, 'creator')->create();
    User::factory()->viewer()->inactive()->create(['name' => 'Inactive Viewer']);

    $this->actingAs($admin)
        ->get(route('administration.users.index', ['role' => UserRole::Contributor->value, 'search' => 'casey']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('administration/users/Index')
            ->has('users.data', 1)
            ->where('users.data.0.role', UserRole::Contributor->value)
            ->where('users.data.0.innovations_count', 2)
            ->has('roles', 5)
            ->where('roles', fn ($roles) => collect($roles)->firstWhere('value', UserRole::SuperAdmin->value)['assignable'] === false
                && collect($roles)->firstWhere('value', UserRole::Viewer->value)['permissions'] === ['catalog.view', 'innovations.view']),
        );

    $this->get(route('administration.users.index', ['status' => UserStatus::Inactive->value]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.name', 'Inactive Viewer'),
        );
});

test('admins can create verified users and the legacy role column follows the assigned role', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->post(route('administration.users.store'), userPayload(['role' => UserRole::Manager->value]))
        ->assertSessionHasNoErrors();

    $user = User::query()->where('email', 'jamie@example.com')->firstOrFail();

    expect($user->hasRole(UserRole::Manager->value))->toBeTrue()
        ->and($user->getRawOriginal('role'))->toBe(UserRole::Manager->value)
        ->and($user->status)->toBe(UserStatus::Active)
        ->and($user->email_verified_at)->not->toBeNull()
        ->and(Hash::check('secret-password-123', $user->password))->toBeTrue();
});

test('admins can update a user, keeping the password unless a new one is given', function () {
    $this->actingAs(User::factory()->admin()->create());
    $user = User::factory()->viewer()->create();
    $originalPassword = $user->password;

    $this->put(route('administration.users.update', $user), userPayload([
        'password' => '',
        'password_confirmation' => '',
        'role' => UserRole::Contributor->value,
        'status' => UserStatus::Inactive->value,
    ]))->assertSessionHasNoErrors();

    $user->refresh();

    expect($user->name)->toBe('Jamie Cruz')
        ->and($user->password)->toBe($originalPassword)
        ->and($user->status)->toBe(UserStatus::Inactive)
        ->and($user->getRoleNames()->all())->toBe([UserRole::Contributor->value])
        ->and($user->getRawOriginal('role'))->toBe(UserRole::Contributor->value);
});

test('only a super admin can assign the super admin role or edit a super admin', function () {
    $admin = User::factory()->admin()->create();
    $superAdmin = User::factory()->superAdmin()->create();
    $viewer = User::factory()->viewer()->create();

    $this->actingAs($admin);

    $this->post(route('administration.users.store'), userPayload(['role' => UserRole::SuperAdmin->value]))
        ->assertSessionHasErrors('role');
    $this->put(route('administration.users.update', $viewer), userPayload(['role' => UserRole::SuperAdmin->value]))
        ->assertSessionHasErrors('role');
    $this->put(route('administration.users.update', $superAdmin), userPayload(['role' => UserRole::SuperAdmin->value]))
        ->assertForbidden();
    $this->patch(route('administration.users.status', $superAdmin))->assertForbidden();

    expect($viewer->fresh()->hasRole(UserRole::SuperAdmin->value))->toBeFalse()
        ->and($superAdmin->fresh()->isActive())->toBeTrue();

    $this->actingAs($superAdmin)
        ->put(route('administration.users.update', $viewer), userPayload(['role' => UserRole::SuperAdmin->value]))
        ->assertSessionHasNoErrors();

    expect($viewer->fresh()->hasRole(UserRole::SuperAdmin->value))->toBeTrue();
});

test('users cannot deactivate themselves or change their own role', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $this->put(route('administration.users.update', $admin), userPayload([
        'email' => $admin->email,
        'role' => UserRole::Viewer->value,
        'status' => UserStatus::Inactive->value,
    ]))->assertSessionHasErrors(['role', 'status']);

    $this->patch(route('administration.users.status', $admin))->assertForbidden();

    expect($admin->fresh()->isActive())->toBeTrue()
        ->and($admin->fresh()->hasRole(UserRole::Admin->value))->toBeTrue();
});

test('deactivating keeps the user and their innovations, and can be reversed', function () {
    $this->actingAs(User::factory()->admin()->create());
    $user = User::factory()->contributor()->create();
    $innovation = Innovation::factory()->for($user, 'creator')->create();

    $this->patch(route('administration.users.status', $user))->assertRedirect();

    expect($user->fresh()->status)->toBe(UserStatus::Inactive)
        ->and($innovation->fresh()->created_by)->toBe($user->id);

    $this->patch(route('administration.users.status', $user));

    expect($user->fresh()->status)->toBe(UserStatus::Active);
});

test('admins can issue a temporary password that replaces the old one and is shown once', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->viewer()->create(['password' => 'old-password']);

    $this->actingAs($admin)
        ->post(route('administration.users.temporary-password', $user))
        ->assertRedirect()
        ->assertInertiaFlash('temporaryPassword.userName', $user->name);

    $issued = session('inertia.flash_data.temporaryPassword.password');
    $user->refresh();

    expect($issued)->toBeString()->toMatch('/^EF-[A-Za-z0-9]{4}-[A-Za-z0-9]{4}-\d{4}$/')
        ->and(Hash::check($issued, $user->password))->toBeTrue()
        ->and(Hash::check('old-password', $user->password))->toBeFalse();
});

test('temporary passwords cannot be issued to yourself, to a super admin by an admin, or by a viewer', function () {
    $admin = User::factory()->admin()->create();
    $superAdmin = User::factory()->superAdmin()->create();
    $viewer = User::factory()->viewer()->create();

    $this->actingAs($admin)->post(route('administration.users.temporary-password', $admin))->assertForbidden();
    $this->actingAs($admin)->post(route('administration.users.temporary-password', $superAdmin))->assertForbidden();
    $this->actingAs($viewer)->post(route('administration.users.temporary-password', $admin))->assertForbidden();
});
