<?php

namespace Illuminate\Tests\Integration\Database;

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
}

class TestScopeModel1 extends Model
{
    public function scopeExists()
    {
        return true;
    }
}
