<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;

/**
 * @group integration
 */
class EloquentModelScopeTest extends DatabaseTestCase
{
    public function testModelHasScope()
    {
        $model = new TestModel1;

        $this->assertTrue($model->hasScope("exists"));
    }

    public function testModelDoesNotHaveScope()
    {
        $model = new TestModel1;

        $this->assertFalse($model->hasScope("doesNotExist"));
    }
}

class TestModel1 extends Model
{
    public function scopeExists()
    {
        return true;
    }
}
