<?php

namespace Illuminate\Tests\Database;

use PHPUnit\Framework\TestCase;
use Illuminate\Database\Connection;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;

class DatabaseEloquentBelongsToOneSyncReturnValueTypeTest extends TestCase
{
    public function setUp()
    {
        $db = new DB;

        $db->addConnection([
            'driver'    => 'sqlite',
            'database'  => ':memory:',
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
        });

        $this->schema()->create('cards', function ($table) {
            $table->string('id');
            $table->string('title');

            $table->primary('id');
        });

        $this->schema()->create('card_user', function ($table) {
            $table->string('card_id')->unique();
            $table->foreign('card_id')->references('id')->on('cards');
            $table->integer('user_id')->unsigned()->unique();
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    public function tearDown()
    {
        $this->schema()->drop('users');
        $this->schema()->drop('cards');
        $this->schema()->drop('card_user');
    }

    /**
     * Helpers...
     */
    protected function seedData()
    {
        BelongsToOneSyncTestTestUser::create(['id' => 1, 'email' => 'taylorotwell@gmail.com']);
        BelongsToOneSyncTestTestCard::insert([
            ['id' => '7b7306ae-5a02-46fa-a84c-9538f45c7dd4', 'title' => 'uuid title'],
            ['id' => (string) (PHP_INT_MAX + 1), 'title' => 'Another title'],
            ['id' => '1', 'title' => 'Another title'],
        ]);
    }

    public function testSyncReturnValueType()
    {
        $this->seedData();

        $user = BelongsToOneSyncTestTestUser::query()->first();
        $cardID = BelongsToOneSyncTestTestCard::query()->first();

        $changes = $user->card()->sync([ $cardID->id ]);

        collect($changes['attached'])->map(function ($id) {
            $this->assertTrue(gettype($id) === (new BelongsToOneSyncTestTestCard)->getKeyType());
        });
    }

    /**
     * Get a database connection instance.
     *
     * @return Connection
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

class BelongsToOneSyncTestTestUser extends Eloquent
{
    protected $table = 'users';
    protected $fillable = ['id', 'email'];
    public $timestamps = false;

    public function card()
    {
        return $this->belongsToOne(BelongsToOneSyncTestTestCard::class, 'card_user', 'user_id', 'card_id');
    }
}

class BelongsToOneSyncTestTestCard extends Eloquent
{
    protected $table = 'cards';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = ['id', 'title'];
}
