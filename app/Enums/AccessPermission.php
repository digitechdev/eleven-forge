<?php

namespace App\Enums;

enum AccessPermission: string
{
    case CatalogView = 'catalog.view';
    case CatalogManage = 'catalog.manage';
    case InnovationView = 'innovations.view';
    case InnovationCreate = 'innovations.create';
    case InnovationUpdateAny = 'innovations.update.any';
    case InnovationUpdateOwn = 'innovations.update.own';
    case InnovationRestore = 'innovations.restore';
    case StockPost = 'inventory.post';
    case ProductionManage = 'production.manage';
    case UserManage = 'users.manage';
    case RoleManage = 'roles.manage';

    /**
     * What this permission unlocks in the application.
     */
    public function description(): string
    {
        return match ($this) {
            self::CatalogView => 'Browse inventory items, item and innovation categories, units, and technologies.',
            self::CatalogManage => 'Create, edit, and retire inventory items, categories, units, and technologies.',
            self::InnovationView => 'Open the internal innovation registry, including bills of materials and internal media.',
            self::InnovationCreate => 'Register new innovations.',
            self::InnovationUpdateAny => 'Edit or archive any innovation, including its BOM, technologies, media, and visibility.',
            self::InnovationUpdateOwn => 'Edit or archive innovations the user created.',
            self::InnovationRestore => 'Restore archived (soft-deleted) innovations.',
            self::StockPost => 'Post stock transactions (opening balance, receive, issue, return, adjustment) and reverse them.',
            self::ProductionManage => 'Manage innovation build formulas and build orders, and complete production runs that consume stock.',
            self::UserManage => 'Create users, assign roles, and activate or deactivate accounts.',
            self::RoleManage => 'Create roles and permissions and change what each role grants.',
        };
    }
}
