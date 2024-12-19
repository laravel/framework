<?php

namespace Illuminate\Console\Enums;

enum CronExpressionPosition: int
{
    case Minutes = 1;
    case Hours = 2;
    case DayOfMonth = 3;
    case Month = 4;
    case DayOfWeek = 5;
}
