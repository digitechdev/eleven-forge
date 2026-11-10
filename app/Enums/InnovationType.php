<?php

namespace App\Enums;

enum InnovationType: string
{
    case Software = 'SOFTWARE';
    case Hardware = 'HARDWARE';
    case Hybrid = 'HYBRID';

    public function label(): string
    {
        return match ($this) {
            self::Software => 'Software',
            self::Hardware => 'Hardware',
            self::Hybrid => 'Hybrid',
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
