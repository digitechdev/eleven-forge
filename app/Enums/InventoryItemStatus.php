<?php

namespace App\Enums;

enum InventoryItemStatus: string
{
    case Active = 'ACTIVE';
    case Inactive = 'INACTIVE';
    case Discontinued = 'DISCONTINUED';
}
