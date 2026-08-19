<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class BelongsToManyAggregateTestTestProduct extends Model
{
    protected $table = 'products';
    protected $fillable = ['id'];
    public $timestamps = false;
}
