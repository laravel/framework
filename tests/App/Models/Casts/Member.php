<?php

namespace Illuminate\Tests\App\Models\Casts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Tests\App\ValueObjects\Euro;

class Member extends Model
{
    public $timestamps = false;
    protected $casts = [
        'amount' => Euro::class,
    ];
}
