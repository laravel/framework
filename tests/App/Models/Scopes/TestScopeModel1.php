<?php

namespace Illuminate\Tests\App\Models\Scopes;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Model;

class TestScopeModel1 extends Model
{
    public function scopeExists(Builder $builder)
    {
        return $builder;
    }

    #[Scope]
    protected function existsAsWell(Builder $builder)
    {
        return $builder;
    }

    #[Scope]
    private function existsAsPrivate(Builder $builder)
    {
        return $builder;
    }
}
