<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasLabel
{
    case Admin = 'admin';
    case SiteEngineer = 'site_engineer';
    case Hrd = 'hrd';
    case Client = 'client';

    public function getLabel(): string
    {
        return __("enum.user_role.{$this->value}");
    }
}
