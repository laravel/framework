<?php

declare(strict_types=1);

namespace Illuminate\Tests\Database\Fixtures\Pruning\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

class PrunableTestModelWithoutPrunableRecords extends Model
{
    use Prunable;

    public function pruneAll()
    {
        return 0;
    }
}
