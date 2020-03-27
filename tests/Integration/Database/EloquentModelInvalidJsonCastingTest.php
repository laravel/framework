<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Schema\Blueprint;
use PHPUnit\Framework\TestCase;
use stdClass;

class EloquentModelInvalidJsonCastingTest extends TestCase
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

        $this->createSchema();
    }

    /**
     * Setup the database schema.
     *
     * @return void
     */
    public function createSchema()
    {
        $this->schema()->create('casting_table', function (Blueprint $table) {
            $table->increments('id');
            $table->string('array_attribute');
            $table->string('object_attribute');
            $table->string('json_attribute');
            $table->timestamps();
        });
    }

    /**
     * Tests...
     */
    public function testStringJson()
    {
        $this->connection()->insert(
            'insert into casting_table (id, array_attribute, object_attribute, json_attribute) values (?, ?, ?, ?)',
            [1, '"valid-json-string"', '"valid-json-string"', '"valid-json-string"']
        );

        $model = InvalidJsonCasts::find(1);

        $this->assertSame([], $model->getOriginal('array_attribute'));
        $this->assertSame([], $model->getAttribute('array_attribute'));

        $stdClass = new stdClass;
        $this->assertEquals($stdClass, $model->getOriginal('object_attribute'));
        $this->assertEquals($stdClass, $model->getAttribute('object_attribute'));

        $this->assertSame('valid-json-string', $model->getOriginal('json_attribute'));
        $this->assertSame('valid-json-string', $model->getAttribute('json_attribute'));
    }

    public function testInvalidJson()
    {
        $this->connection()->insert(
            'insert into casting_table (id, array_attribute, object_attribute, json_attribute) values (?, ?, ?, ?)',
            [1, 'invalid json', 'invalid json', 'invalid json']
        );

        $model = InvalidJsonCasts::find(1);

        $this->assertSame([], $model->getOriginal('array_attribute'));
        $this->assertSame([], $model->getAttribute('array_attribute'));

        $stdClass = new stdClass;
        $this->assertEquals($stdClass, $model->getOriginal('object_attribute'));
        $this->assertEquals($stdClass, $model->getAttribute('object_attribute'));

        $this->assertNull($model->getOriginal('json_attribute'));
        $this->assertNull($model->getAttribute('json_attribute'));
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->schema()->drop('casting_table');
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
     * @return \Illuminate\Database\Schema\Builder
     */
    protected function schema()
    {
        return $this->connection()->getSchemaBuilder();
    }
}

/**
 * Eloquent Models...
 */
class InvalidJsonCasts extends Eloquent
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
        'array_attribute'   => 'array',
        'object_attribute'  => 'object',
        'json_attribute'    => 'json',
    ];
}
