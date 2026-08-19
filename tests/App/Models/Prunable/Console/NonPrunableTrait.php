<?php

declare(strict_types=1);

namespace Illuminate\Tests\App\Models\Prunable\Console;

use Illuminate\Database\Eloquent\Prunable;

trait NonPrunableTrait
{
    use Prunable;
}
