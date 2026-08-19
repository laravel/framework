<?php

namespace Illuminate\Tests\App\Models\Scopes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Tests\App\Scopes\ActiveScope;

class EloquentGlobalScopesArrayTestModel extends Model
{
    protected $table = 'table';

    public static function boot()
    {
        static::addGlobalScopes([
            'active_scope' => new ActiveScope,
            fn ($query) => $query->orderBy('name'),
        ]);

        parent::boot();
    }
}
