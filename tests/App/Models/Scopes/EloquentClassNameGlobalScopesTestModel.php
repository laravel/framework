<?php

namespace Illuminate\Tests\App\Models\Scopes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Tests\App\Scopes\ActiveScope;

class EloquentClassNameGlobalScopesTestModel extends Model
{
    protected $table = 'table';

    public static function boot()
    {
        static::addGlobalScope(ActiveScope::class);

        parent::boot();
    }
}
