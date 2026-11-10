<?php

namespace App\Enums;

/**
 * Lifecycle of a build order. Completed and cancelled orders are final.
 */
enum BuildOrderStatus: string
{
    case Draft = 'DRAFT';
    case Planned = 'PLANNED';
    case InProgress = 'IN_PROGRESS';
    case Completed = 'COMPLETED';
    case Cancelled = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Planned => 'Planned',
            self::InProgress => 'In progress',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Whether no further status change is allowed.
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled], true);
    }

    /**
     * Whether production runs can be completed against an order in this status.
     */
    public function acceptsProduction(): bool
    {
        return in_array($this, [self::Planned, self::InProgress], true);
    }

    /**
     * Whether an order may move from this status to the given one.
     */
    public function canTransitionTo(self $target): bool
    {
        return match ($target) {
            self::Planned => $this === self::Draft,
            self::InProgress => $this === self::Planned,
            self::Cancelled => ! $this->isFinal(),
            default => false,
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
