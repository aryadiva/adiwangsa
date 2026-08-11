<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case Planning = 'planning';
    case Active = 'active';
    case OnHold = 'on_hold';
    case Completed = 'completed';

    public function getLabel(): string
    {
        return __('enum.project_status.'.$this->value);
    }
}
