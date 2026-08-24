<?php

declare(strict_types=1);

namespace Illuminate\Tests\Database\Fixtures\Pruning\Models;

use Illuminate\Database\Eloquent\Prunable;

trait NonPrunableTrait
{
    use Prunable;
}
