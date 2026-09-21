<?php

namespace App;

enum InventoryItemType: string
{
    case RawMaterial = 'RAW_MATERIAL';
    case Component = 'COMPONENT';
    case Electronic = 'ELECTRONIC';
    case Mechanical = 'MECHANICAL';
    case Module = 'MODULE';
    case Equipment = 'EQUIPMENT';
    case Consumable = 'CONSUMABLE';
    case Other = 'OTHER';
}
