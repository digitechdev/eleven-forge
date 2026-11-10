<?php

namespace App\Enums;

enum TechnologyCategory: string
{
    case Language = 'Language';
    case Framework = 'Framework';
    case Database = 'Database';
    case AiMl = 'AI / ML';
    case Library = 'Library';
    case Cloud = 'Cloud';
    case Platform = 'Platform';
    case Protocol = 'Protocol';
    case Service = 'Service';
    case Tool = 'Tool';

    public function label(): string
    {
        return match ($this) {
            self::Language => 'Language',
            self::Framework => 'Framework',
            self::Database => 'Database',
            self::AiMl => 'AI / ML',
            self::Library => 'Library',
            self::Cloud => 'Cloud',
            self::Platform => 'Platform',
            self::Protocol => 'Protocol',
            self::Service => 'Service',
            self::Tool => 'Tool',
        };
    }
}
