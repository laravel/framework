<?php

namespace Illuminate\Tests\App\Models\Serialization;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    public $guarded = [];
    public $timestamps = false;

    public function line()
    {
        return $this->hasOne(Line::class);
    }

    public function lines()
    {
        return $this->hasMany(Line::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'lines');
    }
}
