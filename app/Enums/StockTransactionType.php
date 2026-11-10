<?php

namespace App\Enums;

/**
 * Every way stock can move. Each posting is one immutable stock ledger entry.
 */
enum StockTransactionType: string
{
    case OpeningBalance = 'OPENING_BALANCE';
    case Receive = 'RECEIVE';
    case Issue = 'ISSUE';
    case Return = 'RETURN';
    case Adjustment = 'ADJUSTMENT';
    case Reversal = 'REVERSAL';
    case BuildConsumption = 'BUILD_CONSUMPTION';

    public function label(): string
    {
        return match ($this) {
            self::OpeningBalance => 'Opening balance',
            self::Receive => 'Receive',
            self::Issue => 'Issue',
            self::Return => 'Return',
            self::Adjustment => 'Adjustment',
            self::Reversal => 'Reversal',
            self::BuildConsumption => 'Build consumption',
        };
    }

    /**
     * Types a user can post by hand. Reversals and build consumption come from their own flows.
     *
     * @return list<self>
     */
    public static function manual(): array
    {
        return [self::OpeningBalance, self::Receive, self::Issue, self::Return, self::Adjustment];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $type): array => ['value' => $type->value, 'label' => $type->label()], self::cases());
    }
}
