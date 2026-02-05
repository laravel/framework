<?php

namespace Illuminate\Support;

enum BenchmarkTimeUnit: string
{
    case Nanoseconds = 'ns';
    case Microseconds = 'μs';
    case Milliseconds = 'ms';
    case Seconds = 's';

    public function divisor(): int
    {
        return match ($this) {
            self::Nanoseconds => 1,
            self::Microseconds => 1_000,
            self::Milliseconds => 1_000_000,
            self::Seconds => 1_000_000_000,
        };
    }
}
