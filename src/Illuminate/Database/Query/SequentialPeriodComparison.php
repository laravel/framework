<?php

namespace Illuminate\Database\Query;

enum SequentialPeriodComparison: string
{
    case Percent = 'percent';
    case Difference = 'difference';
}
