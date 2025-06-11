<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Builder;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentWithAttributesPendingTest extends TestCase
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
        $this->schema()->dropIfExists((new PendingAttributesModel)->getTable());
    }

    public function testAddsAttributes(): void
    {
        $key = 'a key';
        $value = 'the value';

        $query = PendingAttributesModel::query()
            ->withAttributes([$key => $value], asConditions: false);

        $model = $query->make();

        $this->assertSame($value, $model->$key);
    }

    public function testDoesNotAddWheres(): void
    {
        $key = 'a key';
        $value = 'the value';

        $query = PendingAttributesModel::query()
            ->withAttributes([$key => $value], asConditions: false);

        $wheres = $query->toBase()->wheres;

        // Ensure no wheres exist
        $this->assertEmpty($wheres);
    }

    public function testAddsWithCasts(): void
    {
        $query = PendingAttributesModel::query()
            ->withAttributes([
                'is_admin' => 1,
                'first_name' => 'FIRST',
                'last_name' => 'LAST',
                'type' => PendingAttributesEnum::internal,
            ], asConditions: false);

        $model = $query->make();

        $this->assertSame(true, $model->is_admin);
        $this->assertSame('First', $model->first_name);
        $this->assertSame('Last', $model->last_name);
        $this->assertSame(PendingAttributesEnum::internal, $model->type);

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

        $query = PendingAttributesModel::query()
            ->withAttributes([
                'is_admin' => 1,
                'first_name' => 'FIRST',
                'last_name' => 'LAST',
                'type' => PendingAttributesEnum::internal,
            ], asConditions: false);

        $query->create();

        $model = PendingAttributesModel::first();

        $this->assertSame(true, $model->is_admin);
        $this->assertSame('First', $model->first_name);
        $this->assertSame('Last', $model->last_name);
        $this->assertSame(PendingAttributesEnum::internal, $model->type);
    }

    protected function bootTable(): void
    {
        $this->schema()->create((new PendingAttributesModel)->getTable(), function ($table) {
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
        return PendingAttributesModel::getConnectionResolver()->connection()->getSchemaBuilder();
    }
}

class PendingAttributesModel extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_admin' => 'boolean',
        'type' => PendingAttributesEnum::class,
    ];

    public function setFirstNameAttribute(string $value): void
    {
        $this->attributes['first_name'] = strtolower($value);
    }

    public function getFirstNameAttribute(?string $value): string
    {
        return ucfirst($value);
    }

    protected function lastName(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => ucfirst($value),
            set: fn (string $value) => strtolower($value),
        );
    }
}

enum PendingAttributesEnum: string
{
    case internal = 'int';
}
