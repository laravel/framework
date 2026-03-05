<?php

declare(strict_types=1);

namespace Illuminate\Config;

enum ConfigIntType: string
{
    case DEFAULT = 'default';
    case POSITIVE = 'positive';
    case NEGATIVE = 'negative';
    case NON_POSITIVE = 'nonPositive';
    case NON_NEGATIVE = 'nonNegative';
    case NON_ZERO = 'nonZero';
}
