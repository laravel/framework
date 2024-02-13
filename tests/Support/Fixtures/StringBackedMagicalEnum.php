<?php

namespace Illuminate\Tests\Support\Fixtures;

use Illuminate\Support\Traits\MagicalEnum;

enum StringBackedMagicalEnum: string
{
    use MagicalEnum;

    case Taylor = 'Otwell';
    case Laravel = 'Framework';
}
