<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Model;

class EloquentModelScopeTest extends DatabaseTestCase
{
    public function testModelHasScope()
    {
        $model = new TestScopeModel1;

        $this->assertTrue($model->hasScope('exists'));
    }

    public function testModelDoesNotHaveScope()
    {
        $model = new TestScopeModel1;

        $this->assertFalse($model->hasScope('doesNotExist'));
    }

    public function testModelHasAttributedScope()
    {
        $model = new TestScopeModel1;

        $this->assertTrue($model->hasScope('existsAsWell'));
    }
}

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
}
