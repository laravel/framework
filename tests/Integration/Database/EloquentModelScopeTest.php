<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\Scope as NamedScope;
use Illuminate\Database\Eloquent\Model;

class EloquentModelScopeTest extends DatabaseTestCase
{
    public function testModelHasScope()
    {
        $model = new TestScopeModel1;

        $this->assertTrue($model->hasNamedScope('exists'));
    }

    public function testModelDoesNotHaveScope()
    {
        $model = new TestScopeModel1;

        $this->assertFalse($model->hasNamedScope('doesNotExist'));
    }

    public function testModelHasAttributedScope()
    {
        $model = new TestScopeModel1;

        $this->assertTrue($model->hasNamedScope('existsAsWell'));
    }
}

class TestScopeModel1 extends Model
{
    public function scopeExists(Builder $builder)
    {
        return $builder;
    }

    #[NamedScope]
    protected function existsAsWell(Builder $builder)
    {
        return $builder;
    }
}
