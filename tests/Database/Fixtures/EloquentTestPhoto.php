<?php

namespace Illuminate\Tests\Database\Fixtures;

use Illuminate\Database\Eloquent\Model as Eloquent;

class EloquentTestPhoto extends Eloquent
{
    protected $table = 'photos';
    protected $guarded = [];

    public function imageable()
    {
        return $this->morphTo();
    }
}
