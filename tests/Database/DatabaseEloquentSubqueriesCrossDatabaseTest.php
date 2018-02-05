<?php

namespace Illuminate\Tests\Database;

use PHPUnit\Framework\TestCase;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;

class DatabaseEloquentSubqueriesCrossDatabaseTest extends TestCase
{
    protected $driversTestCompatible = [
        'mysql' => [
            'crossDatabase' => 'select * from `users` where exists (select * from `mysql2`.`orders` where `users`.`id` = `orders`.`user_id`)',
            'sameDatabase' => 'select * from `users` where exists (select * from `posts` where `users`.`id` = `posts`.`user_id`)'
        ],
        'pgsql' => [
            'crossDatabase' => 'select * from "users" where exists (select * from "pgsql2"."orders" where "users"."id" = "orders"."user_id")',
            'sameDatabase' => 'select * from "users" where exists (select * from "posts" where "users"."id" = "posts"."user_id")'
        ],
        'sqlsrv' => [
            'crossDatabase' => 'select * from [users] where exists (select * from [sqlsrv2].[orders] where [users].[id] = [orders].[user_id])',
            'sameDatabase' => 'select * from [users] where exists (select * from [posts] where [users].[id] = [posts].[user_id])'
        ]
    ];

    protected $driversTestNotCompatible = [
        'sqlite' => [
            'crossDatabase' => 'select * from "users" where exists (select * from "orders" where "users"."id" = "orders"."user_id")',
            'sameDatabase' => 'select * from "users" where exists (select * from "posts" where "users"."id" = "posts"."user_id")'
        ]
    ];

    /**
     * Setup the database schema.
     *
     * @return void
     */
    public function setUp()
    {
        $db = new DB;

        foreach (array_keys($this->driversTestCompatible) as $driver) {
            $db->addConnection([
                'driver' => $driver,
                'host' => '127.0.0.1',
                'port' => '3306',
                'database' => $driver.'1',
                'username' => 'test',
                'password' => 'test',
                'unix_socket' => '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => true,
                'engine' => null,
            ], $driver.'1');

            $db->addConnection([
                'driver' => $driver,
                'host' => '127.0.0.1',
                'port' => '3306',
                'database' => $driver.'2',
                'username' => 'test',
                'password' => 'test',
                'unix_socket' => '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => true,
                'engine' => null,
            ], $driver.'2');
        }

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
        // Test compatible / not compatible cross database contains database in subquery
        foreach ($this->driversTestCompatible as $driver => $expextedSql) {
            //Test cross database
            $query = call_user_func_array('Illuminate\Tests\Database\EloquentTestCrossDatabaseUser'.ucFirst($driver).'::whereHas', ['orders']);
            $this->assertEquals($expextedSql['crossDatabase'], $query->toSql());

            //Test same database
            $query = call_user_func_array('Illuminate\Tests\Database\EloquentTestCrossDatabaseUser'.ucFirst($driver).'::whereHas', ['posts']);
            $this->assertEquals($expextedSql['sameDatabase'], $query->toSql());
        }

        // Test no compatible cross database not contains database in subquery
        foreach ($this->driversTestNotCompatible as $driver => $expextedSql) {
            //Test cross database
            $query = call_user_func_array('Illuminate\Tests\Database\EloquentTestCrossDatabaseUser'.ucFirst($driver).'::whereHas', ['orders']);
            $this->assertEquals($expextedSql['crossDatabase'], $query->toSql());

            //Test same database
            $query = call_user_func_array('Illuminate\Tests\Database\EloquentTestCrossDatabaseUser'.ucFirst($driver).'::whereHas', ['posts']);
            $this->assertEquals($expextedSql['sameDatabase'], $query->toSql());
        }
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