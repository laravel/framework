<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Tests\App\Models\Scopes\TestScopeModel1;

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

    public function testModelDoesNotHaveScopeWhenPrivateVisibility()
    {
        $model = new TestScopeModel1;

        $this->assertFalse($model->hasNamedScope('existsAsPrivate'));
    }
}
