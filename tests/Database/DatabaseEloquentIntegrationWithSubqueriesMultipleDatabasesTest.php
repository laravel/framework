<?php

namespace Illuminate\Tests\Database;

use PHPUnit\Framework\TestCase;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;

class DatabaseEloquentIntegrationWithSubqueriesMultipleDatabasesTest extends TestCase
{
    /**
     * Bootstrap Eloquent.
     *
     * @return void
     */
    public function setUp()
    {
        $db = new DB;

        $db->addConnection([
            'driver'    => 'sqlite',
            'database'  => ':memory:',
        ], 'global');

        $db->addConnection([
            'driver'    => 'sqlite',
            'database'  => ':memory:',
        ], 'blog');

        $db->bootEloquent();
        $db->setAsGlobal();

        $this->createSchema();
        $this->createTableRows();
    }

    protected function createTableRows()
    {
        $userOne = EloquentTestUserSubquery::create(['email' => 'taylorotwell@gmail.com']);
        $userTwo = EloquentTestUserSubquery::create(['email' => 'abigailotwell@gmail.com']);

        $userOne->roles()->create(['name' => "Read"]);
        $userOne->roles()->create(['name' => "Execute"]);

        $userOne->posts()->create(['name' => "Post 1"]);
        $userOne->posts()->create(['name' => "Post 2"]);
    }

    protected function createSchema()
    {
        $this->schema('global')->create('users', function ($table) {
            $table->increments('id');
            $table->string('email');
            $table->timestamps();
        });

        $this->schema('global')->create('roles', function ($table) {
            $table->integer('user_id');
            $table->string('name');
            $table->timestamps();
        });

        $this->schema('blog')->create('posts', function ($table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('name');
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
        foreach (['global'] as $connection) {
            $this->schema($connection)->drop('users');
            $this->schema($connection)->drop('roles');
        }

        foreach (['blog'] as $connection) {
            $this->schema($connection)->drop('posts');
        }
    }

    public function testSameDatabaseSubquery()
    {
        // $query = EloquentTestUserSubquery::

        // $this->assertEquals('select * from "table" where "foo" = ? and ("baz" > ?) and "table"."deleted_at" is null', $query->toSql());
        // $this->assertEquals(['bar', 9000], $query->getBindings());
        $query = EloquentTestUserSubquery::whereHas('roles');

        $this->assertEquals('select * from "users" where exists (select * from "posts" where "users"."id" = "posts"."user_id")', $query->toSql());

        // $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $models);
        // $this->assertInstanceOf('Illuminate\Tests\Database\EloquentTestUser', $models[0]);
        // $this->assertEquals('abigailotwell@gmail.com', $models[0]->email);
        // $this->assertEquals(1, $models->count());
    }

    public function testCrossDatabaseSubquery()
    {
        // $query = EloquentTestUserSubquery::

        // $this->assertEquals('select * from "table" where "foo" = ? and ("baz" > ?) and "table"."deleted_at" is null', $query->toSql());
        // $this->assertEquals(['bar', 9000], $query->getBindings());
        $query = EloquentTestUserSubquery::whereHas('posts');
        dd($query);
        $this->assertEquals('select * from "users" where exists (select * from "roles" where "users"."id" = "roles"."user_id")', $query->toSql());

        // $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $models);
        // $this->assertInstanceOf('Illuminate\Tests\Database\EloquentTestUser', $models[0]);
        // $this->assertEquals('abigailotwell@gmail.com', $models[0]->email);
        // $this->assertEquals(1, $models->count());
    }

    /**
     * Helpers...
     */

    /**
     * Get a database connection instance.
     *
     * @return \Illuminate\Database\Connection
     */
    protected function connection($connection = 'global')
    {
        return Eloquent::getConnectionResolver()->connection($connection);
    }

    /**
     * Get a schema builder instance.
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    protected function schema($connection = 'global')
    {
        return $this->connection($connection)->getSchemaBuilder();
    }
}

class EloquentTestUserSubquery extends Eloquent
{
    protected $connection = 'global';
    protected $table = 'users';
    protected $guarded = ['id'];

    public function roles()
    {
        return $this->hasMany('Illuminate\Tests\Database\EloquentTestRoleSubquery', 'user_id', 'id');
    }

    public function posts()
    {
        return $this->hasMany('Illuminate\Tests\Database\EloquentTestPostSubquery', 'user_id', 'id');
    }
}

class EloquentTestRoleSubquery extends Eloquent
{
    protected $connection = 'global';
    protected $table = 'roles';
    protected $guarded = ['id'];

    public function users()
    {
        return $this->belongsTo('Illuminate\Tests\Database\EloquentTestUserSubquery', 'user_id', 'id');
    }
}

class EloquentTestPostSubquery extends Eloquent
{
    protected $connection = 'blog';
    protected $table = 'posts';
    protected $guarded = ['id'];

    public function users()
    {
        return $this->belongsTo('Illuminate\Tests\Database\EloquentTestUserSubquery', 'user_id', 'id');
    }
}
