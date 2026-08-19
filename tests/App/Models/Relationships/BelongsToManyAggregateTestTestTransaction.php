<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class BelongsToManyAggregateTestTestTransaction extends Model
{
    protected $table = 'transactions';
    protected $fillable = ['id', 'value'];
    public $timestamps = false;

    public function allocatedTo()
    {
        return $this
            ->belongsToMany(BelongsToManyAggregateTestTestTransaction::class, 'allocations', 'from_id', 'to_id')
            ->withPivot('quantity');
    }
}
