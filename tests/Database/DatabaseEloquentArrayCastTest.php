<?php

namespace Illuminate\Tests\Database;

use PHPUnit\Framework\TestCase;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;

class DatabaseEloquentArrayCastTest extends TestCase
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
        $this->schema()->create('json_table', function ($table) {
            $table->increments('id');
            $table->string('json_attributes');
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
        $this->schema()->drop('json_table');
    }

    /**
     * Tests...
     */
    public function testSavingJsonAttributesAsArrayToDatabase()
    {
        /** @var TableWithJsonAttribute $model */
        $model = TableWithJsonAttribute::create([
            'json_attributes' => ['key1'=>'value1'],
        ]);
        $this->assertEquals('{"key1":"value1"}', $model->getOriginal('json_attributes'));

        $model = TableWithJsonAttribute::create([
            'json_attributes' => [],
        ]);
        $this->assertEquals('{}', $model->getOriginal('json_attributes'));
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
class TableWithJsonAttribute extends Eloquent
{
    /**
     * @var string
     */
    protected $table = 'json_table';

    /**
     * @var array
     */
    protected $guarded = [];

    /**
     * @var array
     */
    protected $casts = [
        'json_attributes'=>'array',
    ];
}
