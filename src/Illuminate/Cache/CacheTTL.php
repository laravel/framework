<?php

namespace Illuminate\Cache;

enum CacheTTL: int
{
    case SECOND = 1;
    case MINUTE = 60;
    case HOUR = 3600;
    case DAY = 86400;

    public static function second(): int
    {
        return self::SECOND->value;
    }

    public static function seconds(int $seconds): int
    {
        return $seconds * self::SECOND->value;
    }

    public static function minute(): int
    {
        return self::MINUTE->value;
    }

    public static function minutes(int $minutes): int
    {
        return $minutes * self::MINUTE->value;
    }

    public static function hour(): int
    {
        return self::HOUR->value;
    }

    public static function hours(int $hours): int
    {
        return $hours * self::HOUR->value;
    }

    public static function day(): int
    {
        return self::DAY->value;
    }

    public static function days(int $days): int
    {
        return $days * self::DAY->value;
    }

    public static function week(): int
    {
        return self::days(7);
    }

    public static function weeks(int $weeks): int
    {
        return $weeks * self::week();
    }

    public static function month(): int
    {
        return self::days(30);
    }

    public static function months(int $months): int
    {
        return $months * self::month();
    }

    public static function year(): int
    {
        return self::days(365);
    }

    public static function years(int $years): int
    {
        return $years * self::year();
    }
}
