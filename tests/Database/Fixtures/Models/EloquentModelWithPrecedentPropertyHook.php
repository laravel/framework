<?php

namespace Illuminate\Tests\Database\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

class EloquentModelWithPrecedentPropertyHook extends Model
{
    protected $table = 'test';

    protected $bazQuz {
        get => $this->attributes['foo_bar'];
        set {
            $this->attributes['foo_bar'] = $value;
        }
    }

    protected function getBazQuzAttribute()
    {
        return 'invalid';
    }

    protected function setBazQuzAttribute()
    {
        return $this->attributes['foo_bar'] = 'invalid';
    }

    protected function bazQuz(): Attribute
    {
        return Attribute::make(fn () => 'invalid', fn() => ['foo_bar' => 'invalid']);
    }
}
