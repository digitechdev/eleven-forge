<?php

namespace App\Enums;

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

    public function label(): string
    {
        return match ($this) {
            self::RawMaterial => 'Raw material',
            self::Component => 'Component',
            self::Electronic => 'Electronic',
            self::Mechanical => 'Mechanical',
            self::Module => 'Module',
            self::Equipment => 'Equipment',
            self::Consumable => 'Consumable',
            self::Other => 'Other',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $case): array => ['value' => $case->value, 'label' => $case->label()], self::cases());
    }
}
