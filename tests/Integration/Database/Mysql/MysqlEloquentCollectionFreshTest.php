<?php

namespace Illuminate\Tests\Integration\Database\Mysql;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;

/**
 * @group integration
 */
class MysqlEloquentCollectionFreshTest extends MysqlDatabaseTestCase
{
    public function setUp()
    {
        parent::setUp();

        Schema::create('users', function ($table) {
            $table->increments('id');
            $table->string('email');
        });
    }

    protected function tearDown()
    {
        Schema::drop('users');

        parent::tearDown();
    }

    public function test_eloquent_collection_fresh()
    {
        User::insert([
            ['email' => 'laravel@framework.com'],
            ['email' => 'laravel@laravel.com'],
        ]);

        $collection = User::all();

        User::whereKey($collection->pluck('id')->toArray())->delete();

        $this->assertEmpty($collection->fresh()->filter());
    }
}

class User extends Model
{
    protected $guarded = [];
}
