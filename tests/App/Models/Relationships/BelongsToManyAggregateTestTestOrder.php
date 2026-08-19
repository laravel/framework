<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class BelongsToManyAggregateTestTestOrder extends Model
{
    protected $table = 'orders';
    protected $fillable = ['id'];
    public $timestamps = false;

    public function products()
    {
        return $this
            ->belongsToMany(BelongsToManyAggregateTestTestProduct::class, 'order_product', 'order_id', 'product_id')
            ->withPivot('quantity');
    }
}
