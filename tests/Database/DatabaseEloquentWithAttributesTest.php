<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Builder;
use Illuminate\Tests\App\Models\Relationships\WithAttributesEnum;
use Illuminate\Tests\App\Models\Relationships\WithAttributesModel;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentWithAttributesTest extends TestCase
{
    protected function setUp(): void
    {
        $db = new DB;

        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $db->bootEloquent();
        $db->setAsGlobal();
    }

    protected function tearDown(): void
    {
        $this->schema()->dropIfExists((new WithAttributesModel)->getTable());
    }

    public function testAddsAttributes(): void
    {
        $key = 'a key';
        $value = 'the value';

        $query = WithAttributesModel::query()
            ->withAttributes([$key => $value]);

        $model = $query->make();

        $this->assertSame($value, $model->$key);
    }

    public function testAddsWheres(): void
    {
        $key = 'a key';
        $value = 'the value';

        $query = WithAttributesModel::query()
            ->withAttributes([$key => $value]);

        $wheres = $query->toBase()->wheres;

        $this->assertContains([
            'type' => 'Basic',
            'column' => 'with_attributes_models.'.$key,
            'operator' => '=',
            'value' => $value,
            'boolean' => 'and',
        ], $wheres);
    }

    public function testAddsWithCasts(): void
    {
        $query = WithAttributesModel::query()
            ->withAttributes([
                'is_admin' => 1,
                'first_name' => 'FIRST',
                'last_name' => 'LAST',
                'type' => WithAttributesEnum::internal,
            ]);

        $model = $query->make();

        $this->assertTrue($model->is_admin);
        $this->assertSame('First', $model->first_name);
        $this->assertSame('Last', $model->last_name);
        $this->assertSame(WithAttributesEnum::internal, $model->type);

        $this->assertEqualsCanonicalizing([
            'is_admin' => 1,
            'first_name' => 'first',
            'last_name' => 'last',
            'type' => 'int',
        ], $model->getAttributes());
    }

    public function testAddsWithCastsViaDb(): void
    {
        $this->bootTable();

        $query = WithAttributesModel::query()
            ->withAttributes([
                'is_admin' => 1,
                'first_name' => 'FIRST',
                'last_name' => 'LAST',
                'type' => WithAttributesEnum::internal,
            ]);

        $query->create();

        $model = WithAttributesModel::first();

        $this->assertTrue($model->is_admin);
        $this->assertSame('First', $model->first_name);
        $this->assertSame('Last', $model->last_name);
        $this->assertSame(WithAttributesEnum::internal, $model->type);
    }

    protected function bootTable(): void
    {
        $this->schema()->create((new WithAttributesModel)->getTable(), function ($table) {
            $table->id();
            $table->boolean('is_admin');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('type');
            $table->timestamps();
        });
    }

    protected function schema(): Builder
    {
        return WithAttributesModel::getConnectionResolver()->connection()->getSchemaBuilder();
    }
}
