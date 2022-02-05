<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentTimestampsTest extends TestCase
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

    /**
     * Setup the database schema.
     *
     * @return void
     */
    public function createSchema()
    {
        $this->schema()->create('users', function ($table) {
            $table->increments('id');
            $table->string('email')->unique();
            $table->timestamps();
        });

        $this->schema()->create('users_created_at', function ($table) {
            $table->increments('id');
            $table->string('email')->unique();
            $table->string('created_at');
        });

        $this->schema()->create('users_updated_at', function ($table) {
            $table->increments('id');
            $table->string('email')->unique();
            $table->string('updated_at');
        });
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->schema()->drop('users');
        $this->schema()->drop('users_created_at');
        $this->schema()->drop('users_updated_at');
    }

    /**
     * Tests...
     */
    public function testUserWithCreatedAtAndUpdatedAt()
    {
        $now = Carbon::now();
        $user = UserWithCreatedAndUpdated::create([
            'email' => 'test@test.com',
        ]);

        $this->assertEquals($now->toDateTimeString(), $user->created_at->toDateTimeString());
        $this->assertEquals($now->toDateTimeString(), $user->updated_at->toDateTimeString());
    }

    public function testUserWithCreatedAt()
    {
        $now = Carbon::now();
        $user = UserWithCreated::create([
            'email' => 'test@test.com',
        ]);

        $this->assertEquals($now->toDateTimeString(), $user->created_at->toDateTimeString());
    }

    public function testUserWithUpdatedAt()
    {
        $now = Carbon::now();
        $user = UserWithUpdated::create([
            'email' => 'test@test.com',
        ]);

        $this->assertEquals($now->toDateTimeString(), $user->updated_at->toDateTimeString());
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
class UserWithCreatedAndUpdated extends Eloquent
{
    protected $table = 'users';

    protected $guarded = [];
}

class UserWithCreated extends Eloquent
{
    public const UPDATED_AT = null;

    protected $table = 'users_created_at';

    protected $guarded = [];

    protected $dateFormat = 'U';
}

class UserWithUpdated extends Eloquent
{
    public const CREATED_AT = null;

    protected $table = 'users_updated_at';

    protected $guarded = [];

    protected $dateFormat = 'U';
}
