<?php

namespace Database\Seeders;

use App\AccessPermission;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (AccessPermission::cases() as $permission) {
            Permission::findOrCreate($permission->value, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $viewer = [
            AccessPermission::CatalogView,
            AccessPermission::InnovationView,
        ];
        $contributor = [
            ...$viewer,
            AccessPermission::InnovationCreate,
            AccessPermission::InnovationUpdateOwn,
        ];
        $manager = [
            ...$contributor,
            AccessPermission::CatalogManage,
            AccessPermission::InnovationUpdateAny,
            AccessPermission::InnovationRestore,
        ];

        foreach ([
            UserRole::Viewer->value => $viewer,
            UserRole::Contributor->value => $contributor,
            UserRole::Manager->value => $manager,
            UserRole::Admin->value => [...$manager, AccessPermission::UserManage],
            UserRole::SuperAdmin->value => AccessPermission::cases(),
        ] as $name => $permissions) {
            Role::findOrCreate($name, 'web')->givePermissionTo(
                array_map(fn (AccessPermission $permission): string => $permission->value, $permissions),
            );
        }

        User::query()->whereDoesntHave('roles')->chunkById(100, function ($users): void {
            foreach ($users as $user) {
                $legacyRole = UserRole::tryFrom((string) $user->getRawOriginal('role')) ?? UserRole::Viewer;
                $user->assignRole($legacyRole->value);
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
