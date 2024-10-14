<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EloquentAggregateTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('c');
            $table->string('name');
            $table->integer('balance')->nullable();
        });
    }

    public function testMinMax()
    {
        UserAggregateTest::create(['c' => 1, 'name' => 'test-name1', 'balance' => -1]);
        UserAggregateTest::create(['c' => 2, 'name' => 'test-name2', 'balance' => -1]);
        UserAggregateTest::create(['c' => 3, 'name' => 'test-name3', 'balance' => 0]);
        UserAggregateTest::create(['c' => 4, 'name' => 'test-name4', 'balance' => +1]);
        UserAggregateTest::create(['c' => 5, 'name' => 'test-name5', 'balance' => +2]);
        UserAggregateTest::create(['c' => 6, 'name' => 'test-name5', 'balance' => null]);

        $this->assertEquals(-1, UserAggregateTest::query()->min('balance'));
        $this->assertNull(UserAggregateTest::query()->where('name', 'no-name')->min('balance'));
        $this->assertEquals(1, UserAggregateTest::query()->where('c', '>', 3)->min('balance'));

        $this->assertEquals(2, UserAggregateTest::query()->max('balance'));
        $this->assertNull(UserAggregateTest::query()->where('name', 'no-name')->max('balance'));
        $this->assertEquals(0, UserAggregateTest::query()->where('c', '<', 4)->max('balance'));
    }

    public function testAvg()
    {
        UserAggregateTest::create(['c' => 1, 'name' => 'test-name1', 'balance' => -10]);
        UserAggregateTest::create(['c' => 2, 'name' => 'test-name2', 'balance' => -10]);
        UserAggregateTest::create(['c' => 3, 'name' => 'test-name3', 'balance' => 0]);
        UserAggregateTest::create(['c' => 4, 'name' => 'test-name4', 'balance' => +10]);
        UserAggregateTest::create(['c' => 5, 'name' => 'test-name5', 'balance' => +20]);
        UserAggregateTest::create(['c' => 6, 'name' => 'test-name5', 'balance' => null]);

        $this->assertEquals(2, UserAggregateTest::query()->avg('balance'));
        $this->assertNull(UserAggregateTest::query()->where('name', 'no-name')->avg('balance'));
        $this->assertEquals(15, UserAggregateTest::query()->where('c', '>', 3)->avg('balance'));

        $this->assertEquals(2, UserAggregateTest::query()->average('balance'));
        $this->assertNull(UserAggregateTest::query()->where('name', 'no-name')->average('balance'));
        $this->assertEquals(-10, UserAggregateTest::query()->where('c', '<', 3)->average('balance'));
    }

    public function testSum()
    {
        UserAggregateTest::create(['c' => 1, 'name' => 'name-1', 'balance' => -11]);
        UserAggregateTest::create(['c' => 2, 'name' => 'name-2', 'balance' => -10]);
        UserAggregateTest::create(['c' => 3, 'name' => 'name-3', 'balance' => 0]);
        UserAggregateTest::create(['c' => 4, 'name' => 'name-4', 'balance' => +12]);
        UserAggregateTest::create(['c' => 5, 'name' => 'name-5', 'balance' => null]);

        $this->assertEquals(-9, UserAggregateTest::query()->sum('balance'));
        $result = UserAggregateTest::query()->where('name', 'no-name')->sum('balance');
        $this->assertNotNull($result);
        $this->assertEquals(0, $result);
        $this->assertEquals(2, UserAggregateTest::query()->where('c', '>', 1)->sum('balance'));
    }
}

class UserAggregateTest extends Model
{
    protected $table = 'users';
    protected $fillable = ['name', 'c', 'balance'];
    public $timestamps = false;
}
