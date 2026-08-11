<?php

namespace App\Enums;

enum WeatherCondition: string
{
    case Sunny = 'sunny';
    case Rainy = 'rainy';
    case Cloudy = 'cloudy';
    case Stormy = 'stormy';

    public function getLabel(): string
    {
        return __('weather.'.$this->value);
    }
}
