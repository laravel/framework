<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scopes\Scope;

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

    public function testModelHasNewScope()
    {
        $model = new TestScopeModel1;

        $this->assertTrue($model->hasNamedScope('alsoExists'));
    }

    public function testModelDoesNotHaveNewScope()
    {
        $model = new TestScopeModel1;

        $this->assertFalse($model->hasNamedScope('doesNotAlsoExist'));
    }
}

class TestScopeModel1 extends Model
{
    public function scopeExists()
    {
        return true;
    }

    public function alsoExists(): Scope
    {
        return Scope::make(fn () => true);
    }
}
