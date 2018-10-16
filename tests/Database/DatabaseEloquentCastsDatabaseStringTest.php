<?php

namespace Illuminate\Tests\Database;

use PHPUnit\Framework\TestCase;
use judahnator\JsonManipulator\JsonArray;
use judahnator\JsonManipulator\JsonObject;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;

class DatabaseEloquentCastsDatabaseStringTest extends TestCase
{
    public function setUp()
    {
        $db = new DB;

        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $db->bootEloquent();
        $db->setAsGlobal();

        $this->createSchema();
    }

    /**
     * Setup the database schema.
     *
     * @return void
     */
    public function createSchema()
    {
        $this->schema()->create('casting_table', function ($table) {
            $table->increments('id');
            $table->string('array_attributes');
            $table->string('json_attributes');
            $table->string('object_attributes');
            $table->string('json_array_attributes');
            $table->string('json_object_attributes');
            $table->timestamps();
        });
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    public function tearDown()
    {
        $this->schema()->drop('casting_table');
    }

    /**
     * Tests...
     */
    public function testSavingCastedAttributesToDatabase()
    {
        /** @var TableForCasting $model */
        $model = TableForCasting::create([
            'array_attributes' => ['key1'=>'value1'],
            'json_attributes' => ['json_key'=>'json_value'],
            'object_attributes' => ['json_key'=>'json_value'],
            'json_array_attributes' => ['value1', 'value2'],
            'json_object_attributes' => ['key1' => 'value1'],
        ]);
        $this->assertSame('{"key1":"value1"}', $model->getOriginal('array_attributes'));
        $this->assertSame(['key1'=>'value1'], $model->getAttribute('array_attributes'));

        $this->assertSame('{"json_key":"json_value"}', $model->getOriginal('json_attributes'));
        $this->assertSame(['json_key'=>'json_value'], $model->getAttribute('json_attributes'));

        $this->assertSame('{"json_key":"json_value"}', $model->getOriginal('object_attributes'));
        $stdClass = new \stdClass();
        $stdClass->json_key = 'json_value';
        $this->assertEquals($stdClass, $model->getAttribute('object_attributes'));

        $this->assertSame('["value1","value2"]', $model->getOriginal('json_array_attributes'));
        $this->assertSame('value1', $model->getAttribute('json_array_attributes')[0]);
        $this->assertSame('value2', $model->getAttribute('json_array_attributes')[1]);
        $model->getAttribute('json_array_attributes')[1] = 'value2-edited';
        $this->assertSame('value2-edited', $model->getAttribute('json_array_attributes')[1]);

        $this->assertSame('{"key1":"value1"}', $model->getOriginal('json_object_attributes'));
        $this->assertSame('value1', $model->getAttribute('json_object_attributes')->key1);
        $model->getAttribute('json_object_attributes')->key1 = 'value1-edited';
        $model->getAttribute('json_object_attributes')->newkey = 'new-value';
        $this->assertSame('value1-edited', $model->getAttribute('json_object_attributes')->key1);
        $this->assertSame('new-value', $model->getAttribute('json_object_attributes')->newkey);

    }

    public function testSavingCastedEmptyAttributesToDatabase()
    {
        /** @var TableForCasting $model */
        $model = TableForCasting::create([
            'array_attributes' => [],
            'json_attributes' => [],
            'object_attributes' => [],
            'json_array_attributes' => [],
            'json_object_attributes' => new \stdClass(),
        ]);
        $this->assertSame('[]', $model->getOriginal('array_attributes'));
        $this->assertSame([], $model->getAttribute('array_attributes'));

        $this->assertSame('[]', $model->getOriginal('json_attributes'));
        $this->assertSame([], $model->getAttribute('json_attributes'));

        $this->assertSame('[]', $model->getOriginal('object_attributes'));
        $this->assertSame([], $model->getAttribute('object_attributes'));

        $this->assertSame('[]', $model->getOriginal('json_array_attributes'));
        $this->assertInstanceOf(JsonArray::class, $model->getAttribute('json_array_attributes'));

        $this->assertSame('{}', $model->getOriginal('json_object_attributes'));
        $this->assertInstanceOf(JsonObject::class, $model->getAttribute('json_object_attributes'));
    }

    /**
     * Get a database connection instance.
     *
     * @return \Illuminate\Database\Connection
     */
    protected function connection()
    {
        return Eloquent::getConnectionResolver()->connection();
    }

    /**
     * Get a schema builder instance.
     *
     * @return Schema\Builder
     */
    protected function schema()
    {
        return $this->connection()->getSchemaBuilder();
    }
}

/**
 * Eloquent Models...
 */
class TableForCasting extends Eloquent
{
    /**
     * @var string
     */
    protected $table = 'casting_table';

    /**
     * @var array
     */
    protected $guarded = [];

    /**
     * @var array
     */
    protected $casts = [
        'array_attributes' => 'array',
        'json_attributes' => 'json',
        'object_attributes' => 'object',
        'json_array_attributes' => 'json_array',
        'json_object_attributes' => 'json_object',
    ];
}
