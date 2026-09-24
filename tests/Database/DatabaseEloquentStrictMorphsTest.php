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

    public function testGetActualClassNameForMorphReturnsMappedClassWhenRequireMorphMapIsOn()
    {
        Relation::morphMap([
            'test' => TestModel::class,
        ]);

        $this->assertSame(TestModel::class, Model::getActualClassNameForMorph('test'));
    }

    public function testGetActualClassNameForMorphThrowsWhenTypeNotInMorphMapAndRequireMorphMapIsOn()
    {
        $this->expectException(ClassMorphViolationException::class);

        Model::getActualClassNameForMorph('not-in-morph-map');
    }

    public function testGetActualClassNameForMorphFallsBackToRawStringWhenRequireMorphMapIsOff()
    {
        Relation::requireMorphMap(false);

        $this->assertSame(TestModel::class, Model::getActualClassNameForMorph(TestModel::class));
    }

    public function testMorphToLazyLoadingThrowsWhenTypeNotInMorphMapAndRequireMorphMapIsOn()
    {
        $this->expectException(ClassMorphViolationException::class);

        $model = new TestModel;
        $model->relation_type = 'poisoned';

        $model->relation();
    }

    protected function tearDown(): void
    {
        Relation::morphMap([], false);
        Relation::requireMorphMap(false);
    }
}

class TestModel extends Model
{
    public function relation()
    {
        return $this->morphTo();
    }
}

class TestPivotModel extends Pivot
{
}
