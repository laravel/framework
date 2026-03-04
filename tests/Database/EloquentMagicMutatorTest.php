<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class EloquentMagicMutatorTest extends TestCase
{
    protected function tearDown(): void
    {
        Model::preventMagicMutators(false);

        parent::tearDown();
    }

    public function testLegacyGetMutatorWorksWhenToggleIsOff()
    {
        $model = new EloquentMagicMutatorTestModelWithLegacy;
        $model->name = 'taylor';

        $this->assertSame('TAYLOR', $model->name);
    }

    public function testLegacySetMutatorWorksWhenToggleIsOff()
    {
        $model = new EloquentMagicMutatorTestModelWithLegacy;
        $model->name = 'taylor';

        $this->assertSame('TAYLOR', $model->getAttributes()['name']);
    }

    public function testExceptionIsThrownForLegacyGetMutator()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('legacy magic mutator [getNameAttribute()]');

        Model::preventMagicMutators();

        $model = new EloquentMagicMutatorTestModelWithLegacy;
        $model->name;
    }

    public function testLegacySetMutatorStillWorksWhenToggleIsOn()
    {
        Model::preventMagicMutators();

        $model = new EloquentMagicMutatorTestModelWithLegacy;
        $model->name = 'taylor';

        $this->assertSame('TAYLOR', $model->getAttributes()['name']);
    }

    public function testModernAttributeAccessorWorksWhenToggleIsOn()
    {
        Model::preventMagicMutators();

        $model = new EloquentMagicMutatorTestModelWithModern;
        $model->name = 'taylor';

        $this->assertSame('TAYLOR', $model->name);
    }

}

class EloquentMagicMutatorTestModelWithLegacy extends Model
{
    protected $guarded = [];

    public function getNameAttribute($value)
    {
        return strtoupper($value);
    }

    public function setNameAttribute($value)
    {
        $this->attributes['name'] = strtoupper($value);
    }
}

class EloquentMagicMutatorTestModelWithModern extends Model
{
    protected $guarded = [];

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => strtoupper($value),
            set: fn ($value) => strtoupper($value),
        );
    }
}
