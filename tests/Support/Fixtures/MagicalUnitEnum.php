<?php

namespace Illuminate\Tests\Support\Fixtures;

use Illuminate\Support\Traits\MagicalEnum;

enum MagicalUnitEnum
{
    use MagicalEnum;

    case A;
    case B;
    case C;
    case D;
}
