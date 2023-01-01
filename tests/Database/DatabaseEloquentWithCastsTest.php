<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentWithCastsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $db = new DB;

        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

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

class Time extends Eloquent
{
    protected $guarded = [];

    protected $casts = [
        'time' => 'datetime',
    ];
}
