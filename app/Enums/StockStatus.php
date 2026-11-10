<?php

namespace App\Enums;

/**
 * How healthy an item's stock is, at one location or in total, judged on available quantity.
 */
enum StockStatus: string
{
    case In = 'IN_STOCK';
    case Low = 'LOW_STOCK';
    case Out = 'OUT_OF_STOCK';

    public function label(): string
    {
        return match ($this) {
            self::In => 'In stock',
            self::Low => 'Low stock',
            self::Out => 'Out of stock',
        };
    }

    /**
     * Out of stock when nothing is available, low when available is at or below a positive minimum.
     */
    public static function forQuantity(float $available, float $minimumStock): self
    {
        return match (true) {
            $available <= 0 => self::Out,
            $minimumStock > 0 && $available <= $minimumStock => self::Low,
            default => self::In,
        };
    }

    /**
     * A status from a filter value: an enum value, or the short forms "in", "low", and "out".
     */
    public static function fromFilter(?string $value): ?self
    {
        return match (strtolower((string) $value)) {
            'in' => self::In,
            'low' => self::Low,
            'out' => self::Out,
            default => self::tryFrom((string) $value),
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $status): array => ['value' => $status->value, 'label' => $status->label()], self::cases());
    }
}
