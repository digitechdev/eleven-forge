<?php

namespace App;

enum AccessPermission: string
{
    case CatalogView = 'catalog.view';
    case CatalogManage = 'catalog.manage';
    case InnovationView = 'innovations.view';
    case InnovationCreate = 'innovations.create';
    case InnovationUpdateAny = 'innovations.update.any';
    case InnovationUpdateOwn = 'innovations.update.own';
    case InnovationRestore = 'innovations.restore';
    case UserManage = 'users.manage';
    case RoleManage = 'roles.manage';
}
