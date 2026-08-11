<?php

namespace App\Enums;

enum Locale: string
{
    case English = 'en';
    case Indonesian = 'id';

    public function label(): string
    {
        return match ($this) {
            self::English => 'English',
            self::Indonesian => 'Bahasa Indonesia',
        };
    }
}
