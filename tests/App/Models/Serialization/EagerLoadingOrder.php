<?php

namespace Illuminate\Tests\App\Models\Serialization;

use Illuminate\Database\Eloquent\Model;

class EagerLoadingOrder extends Model
{
    public $table = 'orders';
    public $guarded = [];
    public $timestamps = false;
    public $with = ['line', 'lines', 'products'];

    public function line()
    {
        return $this->hasOne(Line::class, 'order_id');
    }

    public function lines()
    {
        return $this->hasMany(Line::class, 'order_id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'lines', 'order_id');
    }
}
