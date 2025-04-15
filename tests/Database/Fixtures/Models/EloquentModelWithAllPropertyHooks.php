<?php

namespace Illuminate\Tests\Database\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

class EloquentModelWithAllPropertyHooks extends Model
{
    protected $table = 'test';

    public $fooFoo {
        get => true;
    }

    public $barBar {
        set {
            //
        }
    }

    public $bazBaz {
        get => true;
        set {
            //
        }
    }

    public $quzQuz;

    public function getCache()
    {
        return static::$attributePropertyHookGetterCache[get_class($this)];
    }

    public function setCache()
    {
        return static::$attributePropertyHookSetterCache[get_class($this)];
    }
}
