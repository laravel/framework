<?php

declare(strict_types=1);

namespace Illuminate\Tests\Database\Pruning\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;

class PrunableTestSoftPrunableModelWithPrunableRecords extends Model
{
    use Prunable, SoftDeletes;

    protected $table = 'prunables';
    protected $connection = 'default';
    public $timestamps = false;

    public function softPrunable()
    {
        return static::where('value', '>=', 3);
    }
}
