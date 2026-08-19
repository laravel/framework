<?php

namespace Illuminate\Tests\App\Models\Casts;

use Illuminate\Database\Eloquent\Model;

class HashedCast extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    public $casts = [
        'password' => 'hashed',
    ];
}
