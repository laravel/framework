<?php

declare(strict_types=1);

namespace Illuminate\Tests\Database\Pruning\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

abstract class AbstractPrunableModel extends Model
{
    use Prunable;
}
