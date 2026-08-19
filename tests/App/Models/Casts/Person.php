<?php

namespace Illuminate\Tests\App\Models\Casts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Tests\App\Casts\AsAddress;

class Person extends Model
{
    protected $guarded = ['id'];
    public $timestamps = false;
    protected $casts = [
        'address' => AsAddress::class,
    ];
}
