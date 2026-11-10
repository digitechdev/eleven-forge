<?php

namespace App\Enums;

enum InnovationStatus: string
{
    case Concept = 'CONCEPT';
    case Development = 'DEVELOPMENT';
    case Prototype = 'PROTOTYPE';
    case Testing = 'TESTING';
    case Completed = 'COMPLETED';
    case Deployed = 'DEPLOYED';
    case Retired = 'RETIRED';

    public function label(): string
    {
        return match ($this) {
            self::Concept => 'Concept',
            self::Development => 'Development',
            self::Prototype => 'Prototype',
            self::Testing => 'Testing',
            self::Completed => 'Completed',
            self::Deployed => 'Deployed',
            self::Retired => 'Retired',
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
