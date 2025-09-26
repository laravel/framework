<?php

namespace Illuminate\Support;

enum CaseMode
{
    case UPPER;
    case LOWER;
    case TITLE;
    case SNAKE;
    case CAMEL;
    case KEBAB;
    case STUDLY;

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
