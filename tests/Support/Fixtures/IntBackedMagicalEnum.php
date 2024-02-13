<?php

namespace Illuminate\Tests\Support\Fixtures;

use Illuminate\Support\Traits\MagicalEnum;

enum IntBackedMagicalEnum: int implements EnumInterface
{
    use MagicalEnum, ExampleTrait;

    case ONE = 1;
    case TOW = 2;
    case THREE = 3;
}
