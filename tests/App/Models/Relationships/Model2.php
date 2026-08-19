<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class Model2 extends Model
{
    public $table = 'two';
    public $timestamps = false;
    protected $guarded = [];

    public function one()
    {
        return $this->belongsTo(Model1::class, 'one_id');
    }
}
