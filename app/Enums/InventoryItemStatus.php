<?php

namespace App\Enums;

enum InventoryItemStatus: string
{
    case Active = 'ACTIVE';
    case Inactive = 'INACTIVE';
    case Discontinued = 'DISCONTINUED';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
            self::Discontinued => 'Discontinued',
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
