<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\App\Models\Relationships\AggregateUser;

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
        AggregateUser::create(['c' => 1, 'name' => 'test-name1', 'balance' => -1]);
        AggregateUser::create(['c' => 2, 'name' => 'test-name2', 'balance' => -1]);
        AggregateUser::create(['c' => 3, 'name' => 'test-name3', 'balance' => 0]);
        AggregateUser::create(['c' => 4, 'name' => 'test-name4', 'balance' => +1]);
        AggregateUser::create(['c' => 5, 'name' => 'test-name5', 'balance' => +2]);
        AggregateUser::create(['c' => 6, 'name' => 'test-name5', 'balance' => null]);

        $this->assertEquals(-1, AggregateUser::query()->min('balance'));
        $this->assertNull(AggregateUser::query()->where('name', 'no-name')->min('balance'));
        $this->assertEquals(1, AggregateUser::query()->where('c', '>', 3)->min('balance'));

        $this->assertEquals(2, AggregateUser::query()->max('balance'));
        $this->assertNull(AggregateUser::query()->where('name', 'no-name')->max('balance'));
        $this->assertEquals(0, AggregateUser::query()->where('c', '<', 4)->max('balance'));
    }

    public function testAvg()
    {
        AggregateUser::create(['c' => 1, 'name' => 'test-name1', 'balance' => -10]);
        AggregateUser::create(['c' => 2, 'name' => 'test-name2', 'balance' => -10]);
        AggregateUser::create(['c' => 3, 'name' => 'test-name3', 'balance' => 0]);
        AggregateUser::create(['c' => 4, 'name' => 'test-name4', 'balance' => +10]);
        AggregateUser::create(['c' => 5, 'name' => 'test-name5', 'balance' => +20]);
        AggregateUser::create(['c' => 6, 'name' => 'test-name5', 'balance' => null]);

        $this->assertEquals(2, AggregateUser::query()->avg('balance'));
        $this->assertNull(AggregateUser::query()->where('name', 'no-name')->avg('balance'));
        $this->assertEquals(15, AggregateUser::query()->where('c', '>', 3)->avg('balance'));

        $this->assertEquals(2, AggregateUser::query()->average('balance'));
        $this->assertNull(AggregateUser::query()->where('name', 'no-name')->average('balance'));
        $this->assertEquals(-10, AggregateUser::query()->where('c', '<', 3)->average('balance'));
    }

    public function testSum()
    {
        AggregateUser::create(['c' => 1, 'name' => 'name-1', 'balance' => -11]);
        AggregateUser::create(['c' => 2, 'name' => 'name-2', 'balance' => -10]);
        AggregateUser::create(['c' => 3, 'name' => 'name-3', 'balance' => 0]);
        AggregateUser::create(['c' => 4, 'name' => 'name-4', 'balance' => +12]);
        AggregateUser::create(['c' => 5, 'name' => 'name-5', 'balance' => null]);

        $this->assertEquals(-9, AggregateUser::query()->sum('balance'));
        $result = AggregateUser::query()->where('name', 'no-name')->sum('balance');
        $this->assertNotNull($result);
        $this->assertEquals(0, $result);
        $this->assertEquals(2, AggregateUser::query()->where('c', '>', 1)->sum('balance'));
    }

    public function testNumericAggregate()
    {
        AggregateUser::create(['c' => 1, 'name' => 'name-1', 'balance' => 40]);
        AggregateUser::create(['c' => 2, 'name' => 'name-2', 'balance' => -40]);
        AggregateUser::create(['c' => 3, 'name' => 'name-3', 'balance' => 0]);
        AggregateUser::create(['c' => 4, 'name' => 'name-4', 'balance' => 20]);
        AggregateUser::create(['c' => 5, 'name' => 'name-5', 'balance' => null]);

        $this->assertEquals(20, AggregateUser::query()->numericAggregate('sum', ['balance']));
        // When calculating the average, rows with NULL values are excluded
        $this->assertEquals(5, AggregateUser::query()->numericAggregate('avg', ['balance']));
        $this->assertEquals(40, AggregateUser::query()->numericAggregate('max', ['balance']));
        $this->assertEquals(-40, AggregateUser::query()->numericAggregate('min', ['balance']));
    }
}
