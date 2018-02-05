<?php

namespace Illuminate\Tests\Database;

use PHPUnit\Framework\TestCase;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;

class DatabaseEloquentSubqueriesCrossDatabaseTest extends TestCase
{
    /**
     * Setup the database schema.
     *
     * @return void
     */
    public function setUp()
    {
        $db = new DB;

        $db->addConnection([
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => '3306',
            'database' => 'mysql1',
            'username' => 'test',
            'password' => 'test',
            'unix_socket' => '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ], 'mysql1');

        $db->addConnection([
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => '3306',
            'database' => 'mysql2',
            'username' => 'test',
            'password' => 'test',
            'unix_socket' => '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ], 'mysql2');

        $db->addConnection([
            'driver' => 'pgsql',
            'host' => '127.0.0.1',
            'port' => '3306',
            'database' => 'pgsql1',
            'username' => 'test',
            'password' => 'test',
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => 'public',
            'sslmode' => 'prefer',
        ], 'pgsql1');

        $db->addConnection([
            'driver' => 'pgsql',
            'host' => '127.0.0.1',
            'port' => '3306',
            'database' => 'pgsql2',
            'username' => 'test',
            'password' => 'test',
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => 'public',
            'sslmode' => 'prefer',
        ], 'pgsql2');

        $db->addConnection([
            'driver' => 'sqlsrv',
            'host' => '127.0.0.1',
            'port' => '3306',
            'database' => 'sqlsrv1',
            'username' => 'test',
            'password' => 'test',
            'charset' => 'utf8',
            'prefix' => '',
        ], 'sqlsrv1');

        $db->addConnection([
            'driver' => 'sqlsrv',
            'host' => '127.0.0.1',
            'port' => '3306',
            'database' => 'sqlsrv2',
            'username' => 'test',
            'password' => 'test',
            'charset' => 'utf8',
            'prefix' => '',
        ], 'sqlsrv2');

        $db->addConnection([
            'driver'    => 'sqlite',
            'database'  => __DIR__.'/database/sqlite1.sqlite',
            'prefix' => '',
        ], 'sqlite1');

        $db->addConnection([
            'driver'    => 'sqlite',
            'database'  => __DIR__.'/database/sqlite2.sqlite',
            'prefix' => '',
        ], 'sqlite2');

        $db->bootEloquent();
        $db->setAsGlobal();
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    public function tearDown()
    {
        Eloquent::unsetConnectionResolver();
    }

    public function testWhereHasAcrossDatabaseConnection()
    {
        // Test MySQL cross database subquery
        $query = EloquentTestCrossDatabaseUserMysql::whereHas('orders', function($query) {
            $query->where('name', 'like', '%a%');
        });
        $this->assertEquals('select * from `users` where exists (select * from `mysql2`.`orders` where `users`.`id` = `orders`.`user_id` and `name` like ?)', $query->toSql());

        // Test MySQL same database subquery
        $query = EloquentTestCrossDatabaseUserMysql::whereHas('posts', function($query) {
            $query->where('name', 'like', '%a%');
        });
        $this->assertEquals('select * from `users` where exists (select * from `posts` where `users`.`id` = `posts`.`user_id` and `name` like ?)', $query->toSql());

        // Test PostgreSQL cross database subquery
        $query = EloquentTestCrossDatabaseUserPgsql::whereHas('orders', function($query) {
            $query->where('name', 'like', '%a%');
        });
        $this->assertEquals('select * from "users" where exists (select * from "pgsql2"."orders" where "users"."id" = "orders"."user_id" and "name" like ?)', $query->toSql());

        // Test PostgreSQL same database subquery
        $query = EloquentTestCrossDatabaseUserPgsql::whereHas('posts', function($query) {
            $query->where('name', 'like', '%a%');
        });
        $this->assertEquals('select * from "users" where exists (select * from "posts" where "users"."id" = "posts"."user_id" and "name" like ?)', $query->toSql());

        // Test SQL Server cross database subquery
        $query = EloquentTestCrossDatabaseUserSqlsrv::whereHas('orders', function($query) {
            $query->where('name', 'like', '%a%');
        });
        $this->assertEquals('select * from [users] where exists (select * from [sqlsrv2].[orders] where [users].[id] = [orders].[user_id] and [name] like ?)', $query->toSql());

        // Test SQL Server same database subquery
        $query = EloquentTestCrossDatabaseUserSqlsrv::whereHas('posts', function($query) {
            $query->where('name', 'like', '%a%');
        });
        $this->assertEquals('select * from [users] where exists (select * from [posts] where [users].[id] = [posts].[user_id] and [name] like ?)', $query->toSql());

        // Test SQL Server cross database subquery
        $query = EloquentTestCrossDatabaseUserSqlite::whereHas('orders', function($query) {
            $query->where('name', 'like', '%a%');
        });
        $this->assertEquals('select * from "users" where exists (select * from "orders" where "users"."id" = "orders"."user_id" and "name" like ?)', $query->toSql());

        // Test SQL Server same database subquery
        $query = EloquentTestCrossDatabaseUserSqlite::whereHas('posts', function($query) {
            $query->where('name', 'like', '%a%');
        });
        $this->assertEquals('select * from "users" where exists (select * from "posts" where "users"."id" = "posts"."user_id" and "name" like ?)', $query->toSql());
    }

    public function testHasAcrossDatabaseConnection()
    {
        // Test MySQL cross database subquery
        $query = EloquentTestCrossDatabaseUserMysql::has('orders');
        $this->assertEquals('select * from `users` where exists (select * from `mysql2`.`orders` where `users`.`id` = `orders`.`user_id`)', $query->toSql());

        // Test MySQL same database subquery
        $query = EloquentTestCrossDatabaseUserMysql::has('posts');
        $this->assertEquals('select * from `users` where exists (select * from `posts` where `users`.`id` = `posts`.`user_id`)', $query->toSql());

        // Test PostgreSQL cross database subquery
        $query = EloquentTestCrossDatabaseUserPgsql::has('orders');
        $this->assertEquals('select * from "users" where exists (select * from "pgsql2"."orders" where "users"."id" = "orders"."user_id")', $query->toSql());

        // Test PostgreSQL same database subquery
        $query = EloquentTestCrossDatabaseUserPgsql::has('posts');
        $this->assertEquals('select * from "users" where exists (select * from "posts" where "users"."id" = "posts"."user_id")', $query->toSql());

        // Test SQL Server cross database subquery
        $query = EloquentTestCrossDatabaseUserSqlsrv::has('orders');
        $this->assertEquals('select * from [users] where exists (select * from [sqlsrv2].[orders] where [users].[id] = [orders].[user_id])', $query->toSql());

        // Test SQL Server same database subquery
        $query = EloquentTestCrossDatabaseUserSqlsrv::has('posts');
        $this->assertEquals('select * from [users] where exists (select * from [posts] where [users].[id] = [posts].[user_id])', $query->toSql());

        // Test SQL Server cross database subquery
        $query = EloquentTestCrossDatabaseUserSqlite::has('orders');
        $this->assertEquals('select * from "users" where exists (select * from "orders" where "users"."id" = "orders"."user_id")', $query->toSql());

        // Test SQL Server same database subquery
        $query = EloquentTestCrossDatabaseUserSqlite::has('posts');
        $this->assertEquals('select * from "users" where exists (select * from "posts" where "users"."id" = "posts"."user_id")', $query->toSql());
    }

    public function testDoesntHasAcrossDatabaseConnection()
    {
        // Test MySQL cross database subquery
        $query = EloquentTestCrossDatabaseUserMysql::doesntHave('orders');
        $this->assertEquals('select * from `users` where not exists (select * from `mysql2`.`orders` where `users`.`id` = `orders`.`user_id`)', $query->toSql());

        // Test MySQL same database subquery
        $query = EloquentTestCrossDatabaseUserMysql::doesntHave('posts');
        $this->assertEquals('select * from `users` where not exists (select * from `posts` where `users`.`id` = `posts`.`user_id`)', $query->toSql());

        // Test PostgreSQL cross database subquery
        $query = EloquentTestCrossDatabaseUserPgsql::doesntHave('orders');
        $this->assertEquals('select * from "users" where not exists (select * from "pgsql2"."orders" where "users"."id" = "orders"."user_id")', $query->toSql());

        // Test PostgreSQL same database subquery
        $query = EloquentTestCrossDatabaseUserPgsql::doesntHave('posts');
        $this->assertEquals('select * from "users" where not exists (select * from "posts" where "users"."id" = "posts"."user_id")', $query->toSql());

        // Test SQL Server cross database subquery
        $query = EloquentTestCrossDatabaseUserSqlsrv::doesntHave('orders');
        $this->assertEquals('select * from [users] where not exists (select * from [sqlsrv2].[orders] where [users].[id] = [orders].[user_id])', $query->toSql());

        // Test SQL Server same database subquery
        $query = EloquentTestCrossDatabaseUserSqlsrv::doesntHave('posts');
        $this->assertEquals('select * from [users] where not exists (select * from [posts] where [users].[id] = [posts].[user_id])', $query->toSql());

        // Test SQL Server cross database subquery
        $query = EloquentTestCrossDatabaseUserSqlite::doesntHave('orders');
        $this->assertEquals('select * from "users" where not exists (select * from "orders" where "users"."id" = "orders"."user_id")', $query->toSql());

        // Test SQL Server same database subquery
        $query = EloquentTestCrossDatabaseUserSqlite::doesntHave('posts');
        $this->assertEquals('select * from "users" where not exists (select * from "posts" where "users"."id" = "posts"."user_id")', $query->toSql());
    }

    public function testWhereDoesntHaveAcrossDatabaseConnection()
    {
        // Test MySQL cross database subquery
        $query = EloquentTestCrossDatabaseUserMysql::whereDoesntHave('orders', function($query) {
            $query->where('name', 'like', '%a%');
        });
        $this->assertEquals('select * from `users` where not exists (select * from `mysql2`.`orders` where `users`.`id` = `orders`.`user_id` and `name` like ?)', $query->toSql());

        // Test MySQL same database subquery
        $query = EloquentTestCrossDatabaseUserMysql::whereDoesntHave('posts', function($query) {
            $query->where('name', 'like', '%a%');
        });
        $this->assertEquals('select * from `users` where not exists (select * from `posts` where `users`.`id` = `posts`.`user_id` and `name` like ?)', $query->toSql());

        // Test PostgreSQL cross database subquery
        $query = EloquentTestCrossDatabaseUserPgsql::whereDoesntHave('orders', function($query) {
            $query->where('name', 'like', '%a%');
        });
        $this->assertEquals('select * from "users" where not exists (select * from "pgsql2"."orders" where "users"."id" = "orders"."user_id" and "name" like ?)', $query->toSql());

        // Test PostgreSQL same database subquery
        $query = EloquentTestCrossDatabaseUserPgsql::whereDoesntHave('posts', function($query) {
            $query->where('name', 'like', '%a%');
        });
        $this->assertEquals('select * from "users" where not exists (select * from "posts" where "users"."id" = "posts"."user_id" and "name" like ?)', $query->toSql());

        // Test SQL Server cross database subquery
        $query = EloquentTestCrossDatabaseUserSqlsrv::whereDoesntHave('orders', function($query) {
            $query->where('name', 'like', '%a%');
        });
        $this->assertEquals('select * from [users] where not exists (select * from [sqlsrv2].[orders] where [users].[id] = [orders].[user_id] and [name] like ?)', $query->toSql());

        // Test SQL Server same database subquery
        $query = EloquentTestCrossDatabaseUserSqlsrv::whereDoesntHave('posts', function($query) {
            $query->where('name', 'like', '%a%');
        });
        $this->assertEquals('select * from [users] where not exists (select * from [posts] where [users].[id] = [posts].[user_id] and [name] like ?)', $query->toSql());

        // Test SQL Server cross database subquery
        $query = EloquentTestCrossDatabaseUserSqlite::whereDoesntHave('orders', function($query) {
            $query->where('name', 'like', '%a%');
        });
        $this->assertEquals('select * from "users" where not exists (select * from "orders" where "users"."id" = "orders"."user_id" and "name" like ?)', $query->toSql());

        // Test SQL Server same database subquery
        $query = EloquentTestCrossDatabaseUserSqlite::whereDoesntHave('posts', function($query) {
            $query->where('name', 'like', '%a%');
        });
        $this->assertEquals('select * from "users" where not exists (select * from "posts" where "users"."id" = "posts"."user_id" and "name" like ?)', $query->toSql());
    }
}

class EloquentTestCrossDatabaseUserMysql extends EloquentTestUser
{
    protected $connection = 'mysql1';
    protected $table = 'users';
    protected $guarded = [];

    public function orders()
    {
        return $this->hasMany('Illuminate\Tests\Database\EloquentTestCrossDatabaseOrderMysql', 'user_id');
    }

    public function posts()
    {
        return $this->hasMany('Illuminate\Tests\Database\EloquentTestCrossDatabasePostMysql', 'user_id');
    }
}

class EloquentTestCrossDatabasePostMysql extends Eloquent
{
    protected $connection = 'mysql1';
    protected $table = 'posts';
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo('Illuminate\Tests\Database\EloquentTestCrossDatabaseUserMysql', 'user_id');
    }
}

class EloquentTestCrossDatabaseOrderMysql extends Eloquent
{
    protected $connection = 'mysql2';
    protected $table = 'orders';
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo('Illuminate\Tests\Database\EloquentTestCrossDatabaseUserMysql', 'user_id');
    }
}

class EloquentTestCrossDatabaseUserPgsql extends EloquentTestUser
{
    protected $connection = 'pgsql1';
    protected $table = 'users';
    protected $guarded = [];

    public function orders()
    {
        return $this->hasMany('Illuminate\Tests\Database\EloquentTestCrossDatabaseOrderPgsql', 'user_id');
    }

    public function posts()
    {
        return $this->hasMany('Illuminate\Tests\Database\EloquentTestCrossDatabasePostPgsql', 'user_id');
    }
}

class EloquentTestCrossDatabasePostPgsql extends Eloquent
{
    protected $connection = 'pgsql1';
    protected $table = 'posts';
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo('Illuminate\Tests\Database\EloquentTestCrossDatabaseUserPgsql', 'user_id');
    }
}

class EloquentTestCrossDatabaseOrderPgsql extends Eloquent
{
    protected $connection = 'pgsql2';
    protected $table = 'orders';
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo('Illuminate\Tests\Database\EloquentTestCrossDatabaseUserPgsql', 'user_id');
    }
}

class EloquentTestCrossDatabaseUserSqlsrv extends EloquentTestUser
{
    protected $connection = 'sqlsrv1';
    protected $table = 'users';
    protected $guarded = [];

    public function orders()
    {
        return $this->hasMany('Illuminate\Tests\Database\EloquentTestCrossDatabaseOrderSqlsrv', 'user_id');
    }

    public function posts()
    {
        return $this->hasMany('Illuminate\Tests\Database\EloquentTestCrossDatabasePostSqlsrv', 'user_id');
    }
}

class EloquentTestCrossDatabasePostSqlsrv extends Eloquent
{
    protected $connection = 'sqlsrv1';
    protected $table = 'posts';
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo('Illuminate\Tests\Database\EloquentTestCrossDatabaseUserSqlsrv', 'user_id');
    }
}

class EloquentTestCrossDatabaseOrderSqlsrv extends Eloquent
{
    protected $connection = 'sqlsrv2';
    protected $table = 'orders';
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo('Illuminate\Tests\Database\EloquentTestCrossDatabaseUserSqlsrv', 'user_id');
    }
}

class EloquentTestCrossDatabaseUserSqlite extends EloquentTestUser
{
    protected $connection = 'sqlite1';
    protected $table = 'users';
    protected $guarded = [];

    public function orders()
    {
        return $this->hasMany('Illuminate\Tests\Database\EloquentTestCrossDatabaseOrderSqlite', 'user_id');
    }

    public function posts()
    {
        return $this->hasMany('Illuminate\Tests\Database\EloquentTestCrossDatabasePostSqlite', 'user_id');
    }
}

class EloquentTestCrossDatabasePostSqlite extends Eloquent
{
    protected $connection = 'sqlite1';
    protected $table = 'posts';
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo('Illuminate\Tests\Database\EloquentTestCrossDatabaseUserSqlite', 'user_id');
    }
}

class EloquentTestCrossDatabaseOrderSqlite extends Eloquent
{
    protected $connection = 'sqlite2';
    protected $table = 'orders';
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo('Illuminate\Tests\Database\EloquentTestCrossDatabaseUserSqlite', 'user_id');
    }
}