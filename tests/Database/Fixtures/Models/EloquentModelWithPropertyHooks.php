<?php

namespace Illuminate\Tests\Database\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

class EloquentModelWithPropertyHooks extends Model
{
    protected $table = 'test';

    protected $fooBar {
        get => isset($this->attributes['foo_bar']) ? strtoupper($this->attributes['foo_bar']) : null;
        set {
            $this->attributes['foo_bar'] = strtolower($value);
        }
    }
}
