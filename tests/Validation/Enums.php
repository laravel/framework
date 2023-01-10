<?php

namespace Illuminate\Tests\Validation;

enum StringStatus: string
{
    case pending = 'pending';
    case done = 'done';
}

enum IntegerStatus: int
{
    case pending = 1;
    case done = 2;
}

enum PureEnum
{
    case one;
    case two;
}

enum MethodStatus: int
{
    case Pending = 1;
    case Done = 2;

    public static function tryFromName($value)
    {
        foreach (self::cases() as $enum) {
            if ($enum->name === $value) {
                return $enum;
            }
        }

        return null;
    }
}
