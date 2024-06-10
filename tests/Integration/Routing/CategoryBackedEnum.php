<?php

namespace Illuminate\Tests\Integration\Routing;

enum CategoryBackedEnum: string
{
    case People = 'people';
    case Fruits = 'fruits';

    public static function fromCode(string $code)
    {
        return match ($code) {
            'c01' => self::People,
            'c02' => self::Fruits,
            default => null,
        };
    }
}
