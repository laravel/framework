<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Tests\App\Enums\Bar;

class EloquentBelongsToModelStubWithBackedEnumCast extends Model
{
    protected $casts = [
        'foreign_key' => Bar::class,
    ];

    public $attributes = [
        'foreign_key' => 5,
    ];
}
