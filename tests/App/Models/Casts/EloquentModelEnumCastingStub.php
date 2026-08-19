<?php

namespace Illuminate\Tests\App\Models\Casts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Tests\Database\StringStatus;

class EloquentModelEnumCastingStub extends Model
{
    protected $casts = ['enumAttribute' => StringStatus::class];
}
