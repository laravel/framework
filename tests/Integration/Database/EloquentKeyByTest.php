<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EloquentKeyByTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email');
            $table->string('address');
        });

        DB::table('users')->insert([
            ['name' => 'Taylor Otwell', 'email' => 'taylor@laravel.com', 'address' => '5th Avenue'],
            ['name' => 'Lortay Wellot', 'email' => 'lortay@laravel.com', 'address' => '4th Street'],
        ]);
    }

    /**
     * @dataProvider keyByDataProvider
     */
    public function testKeyBy($keyBy, $columns, $key, $expected)
    {
        $this->assertEquals($expected, json_encode(UserKeyByTest::query()->keyBy($keyBy)->get($columns)[$key]));
    }

    public static function keyByDataProvider()
    {
        return [
            'Key by name with all columns' => ['name', ['*'], 'Lortay Wellot', '{"id":2,"name":"Lortay Wellot","email":"lortay@laravel.com","address":"4th Street"}'],
            'Key by name with selected columns not including key' => ['name', ['email', 'address'], 'Taylor Otwell', '{"email":"taylor@laravel.com","address":"5th Avenue"}'],
            'Key by name with selected columns including key' => ['name', ['name', 'email', 'address'], 'Taylor Otwell', '{"name":"Taylor Otwell","email":"taylor@laravel.com","address":"5th Avenue"}'],
            'Key by street with selected dot-columns not including key' => ['address', ['users.email'], '5th Avenue', '{"email":"taylor@laravel.com"}'],
            'Key by street with selected dot-columns including key' => ['address', ['users.address', 'email'], '5th Avenue', '{"address":"5th Avenue","email":"taylor@laravel.com"}'],
        ];
    }

    /**
     * @dataProvider keyByWithSelectDataProvider
     */
    public function testKeyByWithSelect($keyBy, $columns, $key, $expected)
    {
        $results = UserKeyByTest::query()->keyBy($keyBy)->select($columns)->get();

        $this->assertSame($expected, $results[$key]->getAttributes());
    }

    public static function keyByWithSelectDataProvider()
    {
        return [
            // The "name" column is supposed to remain empty here since SELECT does not include an extra prepended keyBy column:
            'SELECT without including extra keyBy column' => ['name', ['name', 'address'], 'Taylor Otwell', ['address' => '5th Avenue']],
            // Since we prepend an extra "name" column here, we account for PDO to shift the first column as the array key:
            'SELECT with including extra keyBy column' => ['name', ['name', 'name', 'address'], 'Taylor Otwell', ['name' => 'Taylor Otwell', 'address' => '5th Avenue']],
        ];
    }
}

class UserKeyByTest extends Model
{
    protected $table = 'users';
    protected $guarded = [];
    public $timestamps = false;
}
