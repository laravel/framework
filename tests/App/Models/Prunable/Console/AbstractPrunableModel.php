<?php

declare(strict_types=1);

namespace Illuminate\Tests\App\Models\Prunable\Console;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

abstract class AbstractPrunableModel extends Model
{
    use Prunable;
}
