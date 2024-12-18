<?php

namespace Illuminate\Console\Enums;

enum ScheduleOn: int
{
    case SUNDAY = 0;

    case MONDAY = 1;

    case TUESDAY = 2;

    case WEDNESDAY = 3;

    case THURSDAY = 4;

    case FRIDAY = 5;

    case SATURDAY = 6;
}
