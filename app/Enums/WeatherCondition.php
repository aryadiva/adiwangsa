<?php

namespace App\Enums;

enum WeatherCondition: string
{
    case Sunny = 'sunny';
    case Rainy = 'rainy';
    case Cloudy = 'cloudy';
    case Stormy = 'stormy';
}
