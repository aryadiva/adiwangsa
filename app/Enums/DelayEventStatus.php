<?php

namespace App\Enums;

enum DelayEventStatus: string
{
    case Red = 'red';
    case Yellow = 'yellow';
    case Green = 'green';

    public function label(): string
    {
        return match ($this) {
            self::Red => __('enum.delay_event_status.red'),
            self::Yellow => __('enum.delay_event_status.yellow'),
            self::Green => __('enum.delay_event_status.green'),
        };
    }
}
