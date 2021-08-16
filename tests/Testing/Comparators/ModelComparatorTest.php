<?php

namespace Illuminate\Tests\Testing\Comparators;

use Illuminate\Database\Eloquent\Model;
use Orchestra\Testbench\TestCase;

class ModelComparatorTest extends TestCase
{
    public function testIsEqual()
    {
        $modelA = new TestModel([
            'id' => 100,
        ]);
        $modelB = new TestModel([
            'id' => 100,
        ]);

        $this->assertEquals($modelA, $modelB);
    }

    public function testIgnoresProperties()
    {
        $modelA = new TestModel([
            'id' => 100,
            'text' => 'good',
        ]);
        $modelB = new TestModel([
            'id' => 100,
            'text' => 'bad',
        ]);

        $this->assertEquals($modelA, $modelB);
    }

    public function testIsNotEqualIfDifferentKey()
    {
        $modelA = new TestModel([
            'id' => 100,
        ]);
        $modelB = new TestModel([
            'id' => 200,
        ]);

        $this->assertNotEquals($modelA, $modelB);
    }

    public function testIsNotEqualIfDifferentTable()
    {
        $modelA = new TestModel([
            'id' => 100,
        ]);
        $modelA->setTable('table_a');
        $modelB = new TestModel([
            'id' => 100,
        ]);
        $modelB->setTable('table_b');

        $this->assertNotEquals($modelA, $modelB);
    }

    public function testIsNotEqualIfDifferentConnection()
    {
        $modelA = new TestModel([
            'id' => 100,
        ]);
        $modelA->setConnection('good');
        $modelB = new TestModel([
            'id' => 100,
        ]);
        $modelA->setConnection('bad');

        $this->assertNotEquals($modelA, $modelB);
    }
}

class TestModel extends Model {
    protected $guarded = [];
}
