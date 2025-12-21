<?php

declare(strict_types=1);

namespace Illuminate\Tests\Database\Pruning\Models;

use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PrunableTestSoftDeletedModelWithPrunableRecords extends Model
{
    use MassPrunable, SoftDeletes;

    protected $table = 'prunables';
    protected $connection = 'default';

    public function prunable()
    {
        return static::where('value', '>=', 3);
    }
}
