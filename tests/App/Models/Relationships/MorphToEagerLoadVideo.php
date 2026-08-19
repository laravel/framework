<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Tests\App\Casts\UuidCast;

class MorphToEagerLoadVideo extends Model
{
    protected $table = 'videos';

    public $timestamps = false;

    public $incrementing = false;

    protected $fillable = ['id'];

    protected $keyType = 'string';

    protected $casts = ['id' => UuidCast::class];
}
