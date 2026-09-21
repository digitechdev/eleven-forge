<?php

namespace App;

enum InventoryItemStatus: string
{
    case Active = 'ACTIVE';
    case Inactive = 'INACTIVE';
    case Discontinued = 'DISCONTINUED';
}
