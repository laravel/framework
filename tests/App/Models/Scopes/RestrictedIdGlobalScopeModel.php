<?php

namespace Illuminate\Tests\App\Models\Scopes;

use Illuminate\Database\Eloquent\Model;

class RestrictedIdGlobalScopeModel extends Model
{
    public $table = 'four';
    public $timestamps = false;
    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('app', function ($builder) {
            $builder->where('id', '>', 1);
        });
    }
}
