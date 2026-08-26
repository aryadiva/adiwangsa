<?php

namespace App\Enums;

enum ReportShift: string
{
    case Shift1 = 'shift_1';
    case Shift2 = 'shift_2';
    case Shift3 = 'shift_3';

    public function getLabel(): string
    {
        return __('enum.report_shift.'.$this->value);
    }
}
