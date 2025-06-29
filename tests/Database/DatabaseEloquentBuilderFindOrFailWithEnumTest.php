<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;

class DatabaseEloquentBuilderFindOrFailWithEnumTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchema();
    }

    protected function tearDown(): void
    {
        Schema::drop('test_models');

        parent::tearDown();
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
    }

    public function testItFindsExistingModelWhenUsingEnumId()
    {
        EloquentBuilderFindOrFailWithEnumTestModel::create(['id' => 1, 'name' => 'one']);

        $model = EloquentBuilderFindOrFailWithEnumTestModel::findOrFail(EloquentBuilderFindOrFailWithEnumTestBackedEnum::One);

        $this->assertInstanceOf(EloquentBuilderFindOrFailWithEnumTestModel::class, $model);
        $this->assertTrue($model->exists);
        $this->assertEquals(1, $model->id);
    }

    public function testItThrowsExceptionWhenEnumIdDoesNotExist()
    {
        $this->expectException(ModelNotFoundException::class);
        $this->expectExceptionMessage('No query results for model ['.EloquentBuilderFindOrFailWithEnumTestModel::class.'] '.EloquentBuilderFindOrFailWithEnumTestBackedEnum::Ten->value);

        EloquentBuilderFindOrFailWithEnumTestModel::findOrFail(EloquentBuilderFindOrFailWithEnumTestBackedEnum::Ten);
    }

    protected function createSchema()
    {
        Schema::create('test_models', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
    }
}

class EloquentBuilderFindOrFailWithEnumTestModel extends Model
{
    protected $table = 'test_models';
    public $timestamps = false;
    protected $guarded = [];
}

enum EloquentBuilderFindOrFailWithEnumTestBackedEnum: int
{
    case One = 1;
    case Ten = 10;
}
