<?php

namespace Database\Seeders;

use App\Models\User;
use App\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        foreach (UserRole::cases() as $role) {
            $user = User::firstOrCreate(
                ['email' => strtolower($role->value).'@elevenforge.test'],
                ['name' => $role->label().' Demo', 'password' => Str::random(40)],
            );

            if (! $user->roles()->exists()) {
                $user->assignRole($role->value);
            }
        }
    }
}
