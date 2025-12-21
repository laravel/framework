<?php

declare(strict_types=1);

namespace Illuminate\Tests\Database\Pruning\Models;

use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\ModelsPruned;

class PrunableTestModelWithPrunableRecords extends Model
{
    use MassPrunable;

    protected $table = 'prunables';
    protected $connection = 'default';

    public function pruneAll()
    {
        event(new ModelsPruned(static::class, 10));
        event(new ModelsPruned(static::class, 20));

        return 20;
    }

    public function prunable()
    {
        return static::where('value', '>=', 3);
    }
}
