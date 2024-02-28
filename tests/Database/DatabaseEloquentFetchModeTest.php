<?php

namespace Illuminate\Tests\Database;

use DateTimeInterface;
use Exception;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\FetchMode;
use Illuminate\Support\Carbon;
use Illuminate\Tests\Integration\Database\Fixtures\User;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentFetchModeTest extends TestCase
{
    /**
     * Setup the database schema.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $db = new DB;

        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ], 'second_connection');

        $db->bootEloquent();
        $db->setAsGlobal();

        $this->createSchema();
        $this->createData();
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->schema('default')->drop('users');

        Relation::morphMap([], false);
        Eloquent::unsetConnectionResolver();

        Carbon::setTestNow(null);
    }

    public function testPluck(): void
    {
        $this->assertEquals([
            'Taylor' => 'Manager',
            'Graham' => 'Developer-1',
            'Dries' => 'Developer-1',
            'Tetiana' => 'Developer-2',
            'Mohamed' => 'Developer-1',
            'Lucas' => 'Developer-2',
            'Joseph' => 'Developer-3',
        ], User::query()->pluck('title', 'name')->toArray());

        $this->assertEquals([
            'Manager' => 'Taylor',
            'Developer-1' => 'Mohamed',
            'Developer-2' => 'Lucas',
            'Developer-3' => 'Joseph',
        ], User::query()->pluck('name', 'title')->toArray());
    }

    public function testKeyedArray(): void
    {
        $results = User::query()
            ->select(['title', 'title', 'name'])
            ->mode(FetchMode::keyed())
            ->get();

        $this->assertEquals('Taylor', $results['Manager']->name);
        $this->assertEquals('Mohamed', $results['Developer-1']->name);
        $this->assertEquals('Lucas', $results['Developer-2']->name);
        $this->assertEquals('Joseph', $results['Developer-3']->name);
    }

    public function testCursor(): void
    {
        $results = User::query()
            ->select(['title', 'title', 'name'])
            ->mode(FetchMode::cursor(2))
            ->cursor();

        foreach($results as $result) {
            dump($result->toArray());
        }

    }

    /**
     * Get a database connection instance.
     *
     * @return \Illuminate\Database\ConnectionInterface
     */
    protected function connection($connection = 'default'): ConnectionInterface
    {
        return Eloquent::getConnectionResolver()->connection($connection);
    }

    /**
     * Get a schema builder instance.
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    protected function schema($connection = 'default')
    {
        return $this->connection($connection)->getSchemaBuilder();
    }

    protected function createSchema(): void
    {
        $this->schema('default')->create('users', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('title');
            $table->timestamps();
        });
    }

    protected function createData(): void
    {
        User::create(['name' => 'Taylor', 'title' => 'Manager']);
        User::create(['name' => 'Graham', 'title' => 'Developer-1']);
        User::create(['name' => 'Dries', 'title' => 'Developer-1']);
        User::create(['name' => 'Tetiana', 'title' => 'Developer-2']);
        User::create(['name' => 'Mohamed', 'title' => 'Developer-1']);
        User::create(['name' => 'Lucas', 'title' => 'Developer-2']);
        User::create(['name' => 'Joseph', 'title' => 'Developer-3']);
    }
}
