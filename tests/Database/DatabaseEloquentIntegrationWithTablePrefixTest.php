<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\Relation;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentIntegrationWithTablePrefixTest extends TestCase
{
    /**
     * Bootstrap Eloquent.
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

        $db->bootEloquent();
        $db->setAsGlobal();

        Eloquent::getConnectionResolver()->connection()->setTablePrefix('prefix_');

        $this->createSchema();
    }

    protected function createSchema()
    {
        $this->schema('default')->create('users', function ($table) {
            $table->increments('id');
            $table->string('email');
            $table->timestamps();
        });

        $this->schema('default')->create('friends', function ($table) {
            $table->integer('user_id');
            $table->integer('friend_id');
        });

        $this->schema('default')->create('posts', function ($table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('parent_id')->nullable();
            $table->string('name');
            $table->timestamps();
        });

        $this->schema('default')->create('photos', function ($table) {
            $table->increments('id');
            $table->morphs('imageable');
            $table->string('name');
            $table->timestamps();
        });
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        foreach (['default'] as $connection) {
            $this->schema($connection)->drop('users');
            $this->schema($connection)->drop('friends');
            $this->schema($connection)->drop('posts');
            $this->schema($connection)->drop('photos');
        }

        Relation::morphMap([], false);
    }

    public function testBasicModelHydration()
    {
        EloquentTestUser::create(['email' => 'taylorotwell@gmail.com']);
        EloquentTestUser::create(['email' => 'abigailotwell@gmail.com']);

        $models = EloquentTestUser::fromQuery('SELECT * FROM prefix_users WHERE email = ?', ['abigailotwell@gmail.com']);

        $this->assertInstanceOf(Collection::class, $models);
        $this->assertInstanceOf(EloquentTestUser::class, $models[0]);
        $this->assertSame('abigailotwell@gmail.com', $models[0]->email);
        $this->assertCount(1, $models);
    }

    /**
     * Helpers...
     */

    /**
     * Get a database connection instance.
     *
     * @return \Illuminate\Database\Connection
     */
    protected function connection($connection = 'default')
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
}
