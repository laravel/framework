<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\ClassMorphViolationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\Relation;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentStrictMorphsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Relation::requireMorphMap();
    }

    public function testStrictModeThrowsAnExceptionOnClassMap()
    {
        $this->expectException(ClassMorphViolationException::class);

        $model = new TestModel;

        $model->getMorphClass();
    }

    public function testStrictModeDoesNotThrowExceptionWhenMorphMap()
    {
        $model = new TestModel;

        Relation::morphMap([
            'test' => TestModel::class,
        ]);

        $morphName = $model->getMorphClass();
        $this->assertSame('test', $morphName);
    }

    public function testMapsCanBeEnforcedInOneMethod()
    {
        $model = new TestModel;

        Relation::requireMorphMap(false);

        Relation::enforceMorphMap([
            'test' => TestModel::class,
        ]);

        $morphName = $model->getMorphClass();
        $this->assertSame('test', $morphName);
    }

    public function testMapIgnoreGenericPivotClass()
    {
        $pivotModel = new Pivot();

        $pivotModel->getMorphClass();
    }

    public function testMapCanBeEnforcedToCustomPivotClass()
    {
        $this->expectException(ClassMorphViolationException::class);

        $pivotModel = new TestPivotModel();

        $pivotModel->getMorphClass();
    }

    public function testStaticMethodForMorphClassRetrieval()
    {
        // Test without a morph map
        Relation::requireMorphMap(true);
        $this->expectException(ClassMorphViolationException::class);
        TestModel::getMorphClassStatic();

        Relation::requireMorphMap(false);

        Relation::morphMap([
            'test' => TestModel::class,
        ]);

        $morphName = TestModel::getMorphClassStatic();
        $this->assertSame('test', $morphName);

        $pivotMorphName = TestPivotModel::getMorphClassStatic();
        $this->assertSame(TestPivotModel::class, $pivotMorphName);
    }

    public function testStaticMethodWithEmptyMorphMap()
    {
        Relation::morphMap([]);

        $this->expectException(ClassMorphViolationException::class);
        TestModel::getMorphClassStatic();
    }

    public function testStaticMethodWithNonExistingClassInMorphMap()
    {
        Relation::morphMap([
            'test' => SomeOtherModel::class,
        ]);

        $this->expectException(ClassMorphViolationException::class);
        TestModel::getMorphClassStatic();
    }

    public function testStaticMethodExceptionForRequiredMorphMap()
    {
        Relation::requireMorphMap(true);

        $this->expectException(ClassMorphViolationException::class);
        TestModel::getMorphClassStatic();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Relation::morphMap([], false);
        Relation::requireMorphMap(false);
    }
}

class TestModel extends Model
{
}

class TestPivotModel extends Pivot
{
}
