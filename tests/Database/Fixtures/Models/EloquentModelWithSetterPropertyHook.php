<?php

namespace Illuminate\Tests\Database\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

class EloquentModelWithSetterPropertyHook extends Model
{
    protected $table = 'test';

    protected $fooBar {
        set {
            $this->attributes['foo_bar'] = strtolower($value);
        }
    }
}
