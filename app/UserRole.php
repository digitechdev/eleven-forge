<?php

namespace App;

enum UserRole: string
{
    case SuperAdmin = 'SUPER_ADMIN';
    case Admin = 'ADMIN';
    case Manager = 'MANAGER';
    case Contributor = 'CONTRIBUTOR';
    case Viewer = 'VIEWER';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Admin => 'Admin',
            self::Manager => 'Manager',
            self::Contributor => 'Contributor',
            self::Viewer => 'Viewer',
        };
    }
}
