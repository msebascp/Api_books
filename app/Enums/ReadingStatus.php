<?php

declare(strict_types=1);

namespace App\Enums;

enum ReadingStatus: string
{
    case ToRead = 'to_read';
    case Reading = 'reading';
    case Read = 'read';
    case Dnf = 'dnf';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
