<?php

namespace Illuminate\Tests\App\Models\Scopes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Tests\App\Scopes\ActiveScope;

class EloquentGlobalScopesTestModel extends Model
{
    protected $table = 'table';

    public static function boot()
    {
        static::addGlobalScope(new ActiveScope);

        parent::boot();
    }
}
