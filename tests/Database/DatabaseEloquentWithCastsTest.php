<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Schema\Blueprint;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentWithCastsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $db = new DB;

        $db->addConnection([
            'driver'   => 'sqlite',
            'database' => ':memory:',
        ]);

        $db->addConnection([
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'database' => 'forge',
            'username' => 'root',
            'password' => '',
        ], 'mysql');

        $db->addConnection([
            'driver' => 'pgsql',
            'host' => 'localhost',
            'database' => 'forge',
            'username' => 'forge',
        ], 'pgsql');

        $db->bootEloquent();
        $db->setAsGlobal();

        $this->createSchema();
    }

    protected function createSchema()
    {
        $this->schema()->create('times', function ($table) {
            $table->increments('id');
            $table->time('time');
            $table->timestamps();
        });

        $this->schema()->create('json_arrays', function (Blueprint $table) {
            $table->increments('id');
            $table->json('sample_data');
        });

        $this->schema()->create('json_objects', function (Blueprint $table) {
            $table->increments('id');
            $table->json('sample_data');
        });

        $this->schema('mysql')->create('json_arrays', function (Blueprint $table) {
            $table->increments('id');
            $table->json('sample_data');
        });

        $this->schema('mysql')->create('json_objects', function (Blueprint $table) {
            $table->increments('id');
            $table->json('sample_data');
        });

        $this->schema('pgsql')->create('json_arrays', function (Blueprint $table) {
            $table->increments('id');
            $table->json('sample_data');
        });

        $this->schema('pgsql')->create('json_objects', function (Blueprint $table) {
            $table->increments('id');
            $table->json('sample_data');
        });
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->schema('mysql')->drop('json_arrays');

        $this->schema('mysql')->drop('json_objects');

        $this->schema('pgsql')->drop('json_arrays');

        $this->schema('pgsql')->drop('json_objects');
    }

    public function testWithFirstOrNew()
    {
        $time1 = Time::query()->withCasts(['time' => 'string'])
            ->firstOrNew(['time' => '07:30']);

        Time::query()->insert(['time' => '07:30']);

        $time2 = Time::query()->withCasts(['time' => 'string'])
            ->firstOrNew(['time' => '07:30']);

        $this->assertSame('07:30', $time1->time);
        $this->assertSame($time1->time, $time2->time);
    }

    public function testWithFirstOrCreate()
    {
        $time1 = Time::query()->withCasts(['time' => 'string'])
            ->firstOrCreate(['time' => '07:30']);

        $time2 = Time::query()->withCasts(['time' => 'string'])
            ->firstOrCreate(['time' => '07:30']);

        $this->assertSame($time1->id, $time2->id);
    }

    public function testJsonArraysGetChangesIsEmptyOnAssignSameArray()
    {
        $sample_data = [
            'aa' => 1,
            'b' => 2,
        ];

        $model = new JsonArray();
        $model->sample_data = $sample_data;
        $model->save();

        $newModel = JsonArray::find(1);
        $newModel->sample_data = $sample_data;
        $newModel->save();

        $mysqlModel = new JsonArray();
        $mysqlModel->setConnection('mysql');
        $mysqlModel->sample_data = $sample_data;
        $model->save();

        $newMysqlModel = new JsonArray();
        $newMysqlModel->setConnection('mysql');
        $newMysqlModel::find(1);
        $newMysqlModel->sample_data = $sample_data;
        $newMysqlModel->save();

        $pgsqlModel = new JsonArray();
        $pgsqlModel->setConnection('pgsql');
        $pgsqlModel->sample_data = $sample_data;
        $pgsqlModel->save();

        $newPgsqlModel = new JsonArray();
        $newPgsqlModel->setConnection('pgsql');
        $newPgsqlModel->sample_data = $sample_data;
        $newPgsqlModel->save();

        $this->assertEmpty($newModel->getChanges());

        $this->assertEmpty($newMysqlModel->getChanges());

        $this->assertEmpty($newPgsqlModel->getChanges());
    }

    public function testJsonObjectsGetChangesIsEmptyOnAssignSameArray()
    {
        $sample_data = [
            'aa' => 1,
            'b' => 2,
        ];

        $model = new JsonObject();
        $model->sample_data = $sample_data;
        $model->save();

        $newModel = JsonObject::find(1);
        $newModel->sample_data = $sample_data;
        $newModel->save();

        $mysqlModel = new JsonObject();
        $mysqlModel->setConnection('mysql');
        $mysqlModel->sample_data = $sample_data;
        $mysqlModel->save();

        $newMysqlModel = new JsonObject();
        $newMysqlModel->setConnection('mysql');
        $newMysqlModel->sample_data = $sample_data;
        $newMysqlModel->save();

        $pgsqlModel = new JsonObject();
        $pgsqlModel->setConnection('pgsql');
        $pgsqlModel->sample_data = $sample_data;
        $pgsqlModel->save();

        $newPgsqlModel = new JsonObject();
        $newPgsqlModel->setConnection('pgsql');
        $newPgsqlModel->sample_data = $sample_data;
        $newPgsqlModel->save();

        $this->assertEmpty($newModel->getChanges());

        $this->assertEmpty($newMysqlModel->getChanges());

        $this->assertEmpty($newPgsqlModel->getChanges());
    }

    /**
     * Get a database connection instance.
     *
     * @return \Illuminate\Database\Connection
     */
    protected function connection($connection = null)
    {
        return Eloquent::getConnectionResolver()->connection($connection);
    }

    /**
     * Get a schema builder instance.
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    protected function schema($connection = null)
    {
        return $this->connection($connection)->getSchemaBuilder();
    }
}

class Time extends Eloquent
{
    protected $guarded = [];

    protected $casts = [
        'time' => 'datetime',
    ];
}

class JsonObject extends Model
{
    public $timestamps = false;

    protected $casts = [
        'sample_data' => 'object',
    ];
}

class JsonArray extends Model
{
    public $timestamps = false;

    protected $casts = [
        'sample_data' => 'array',
    ];
}
