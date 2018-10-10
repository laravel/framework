<?php

namespace Illuminate\Tests\Database;

use stdClass;
use PHPUnit\Framework\TestCase;
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
        ]);
        $this->assertSame('{"key1":"value1"}', $model->getOriginal('array_attributes'));
        $this->assertSame(['key1'=>'value1'], $model->getAttribute('array_attributes'));

        $this->assertSame('{"json_key":"json_value"}', $model->getOriginal('json_attributes'));
        $this->assertSame(['json_key'=>'json_value'], $model->getAttribute('json_attributes'));

        $this->assertSame('{"json_key":"json_value"}', $model->getOriginal('object_attributes'));
        $stdClass = new stdClass();
        $stdClass->json_key = 'json_value';
        $this->assertEquals($stdClass, $model->getAttribute('object_attributes'));
    }

    public function testSavingCastedEmptyAttributesToDatabase()
    {
        /** @var TableForCasting $model */
        $model = TableForCasting::create([
            'array_attributes' => [],
            'json_attributes' => [],
            'object_attributes' => [],
        ]);
        $this->assertSame('[]', $model->getOriginal('array_attributes'));
        $this->assertSame([], $model->getAttribute('array_attributes'));

        $this->assertSame('[]', $model->getOriginal('json_attributes'));
        $this->assertSame([], $model->getAttribute('json_attributes'));

        $this->assertSame('[]', $model->getOriginal('object_attributes'));
        $this->assertSame([], $model->getAttribute('object_attributes'));
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
    ];
}
