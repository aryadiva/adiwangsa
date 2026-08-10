<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case SiteEngineer = 'site_engineer';
    case Client = 'client';
}
