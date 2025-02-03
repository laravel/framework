<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\NamedScope;

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
    public function scopeExists()
    {
        return true;
    }

    #[NamedScope]
    public function existsAsWell()
    {
        return true;
    }
}
