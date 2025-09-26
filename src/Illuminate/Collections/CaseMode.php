<?php

namespace Illuminate\Support;

enum CaseMode: int
{
    case UPPER = 1;
    case LOWER = 2;
    case TITLE = 3;
    case SNAKE = 4;
    case CAMEL = 5;
    case KEBAB = 6;
    case STUDLY = 7;

    /**
     * Convert the given string to the case mode.
     */
    public function convert(string $key): string
    {
        $str = Str::of($key);

        return match ($this) {
            self::UPPER => $str->upper()->value(),
            self::LOWER => $str->lower()->value(),
            self::TITLE => $str->title()->value(),
            self::SNAKE => $str->snake()->value(),
            self::CAMEL => $str->camel()->value(),
            self::KEBAB => $str->kebab()->value(),
            self::STUDLY => $str->studly()->value(),
        };
    }
}
