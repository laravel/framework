<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Pagination\Cursor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EloquentCursorPaginateTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('test_posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->timestamps();
        });

        Schema::create('test_users', function ($table) {
            $table->increments('id');
            $table->timestamps();
        });
    }

    public function testCursorPaginationOnTopOfColumns()
    {
        for ($i = 1; $i <= 50; $i++) {
            TestPost::create([
                'title' => 'Title '.$i,
            ]);
        }

        $this->assertCount(15, TestPost::cursorPaginate(15, ['id', 'title']));
    }

    public function testPaginationWithUnion()
    {
        TestPost::create(['title' => 'Hello world', 'user_id' => 1]);
        TestPost::create(['title' => 'Goodbye world', 'user_id' => 2]);
        TestPost::create(['title' => 'Howdy', 'user_id' => 3]);
        TestPost::create(['title' => '4th', 'user_id' => 4]);

        $table1 = TestPost::query()->whereIn('user_id', [1, 2]);
        $table2 = TestPost::query()->whereIn('user_id', [3, 4]);

        $result = $table1->unionAll($table2)
            ->orderBy('user_id', 'desc')
            ->cursorPaginate(1);

        $this->assertSame(['user_id'], $result->getOptions()['parameters']);
    }

    public function testPaginationWithDistinct()
    {
        for ($i = 1; $i <= 3; $i++) {
            TestPost::create(['title' => 'Hello world']);
            TestPost::create(['title' => 'Goodbye world']);
        }

        $query = TestPost::query()->distinct();

        $this->assertEquals(6, $query->get()->count());
        $this->assertEquals(6, $query->count());
        $this->assertCount(6, $query->cursorPaginate()->items());
    }

    public function testPaginationWithWhereClause()
    {
        for ($i = 1; $i <= 3; $i++) {
            TestPost::create(['title' => 'Hello world', 'user_id' => null]);
            TestPost::create(['title' => 'Goodbye world', 'user_id' => 2]);
        }

        $query = TestPost::query()->whereNull('user_id');

        $this->assertEquals(3, $query->get()->count());
        $this->assertEquals(3, $query->count());
        $this->assertCount(3, $query->cursorPaginate()->items());
    }

    public function testPaginationWithHasClause()
    {
        for ($i = 1; $i <= 3; $i++) {
            TestUser::create();
            TestPost::create(['title' => 'Hello world', 'user_id' => null]);
            TestPost::create(['title' => 'Goodbye world', 'user_id' => 2]);
            TestPost::create(['title' => 'Howdy', 'user_id' => 3]);
        }

        $query = TestUser::query()->has('posts');

        $this->assertEquals(2, $query->get()->count());
        $this->assertEquals(2, $query->count());
        $this->assertCount(2, $query->cursorPaginate()->items());
    }

    public function testPaginationWithWhereHasClause()
    {
        for ($i = 1; $i <= 3; $i++) {
            TestUser::create();
            TestPost::create(['title' => 'Hello world', 'user_id' => null]);
            TestPost::create(['title' => 'Goodbye world', 'user_id' => 2]);
            TestPost::create(['title' => 'Howdy', 'user_id' => 3]);
        }

        $query = TestUser::query()->whereHas('posts', function ($query) {
            $query->where('title', 'Howdy');
        });

        $this->assertEquals(1, $query->get()->count());
        $this->assertEquals(1, $query->count());
        $this->assertCount(1, $query->cursorPaginate()->items());
    }

    public function testPaginationWithWhereExistsClause()
    {
        for ($i = 1; $i <= 3; $i++) {
            TestUser::create();
            TestPost::create(['title' => 'Hello world', 'user_id' => null]);
            TestPost::create(['title' => 'Goodbye world', 'user_id' => 2]);
            TestPost::create(['title' => 'Howdy', 'user_id' => 3]);
        }

        $query = TestUser::query()->whereExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('test_posts')
                ->whereColumn('test_posts.user_id', 'test_users.id');
        });

        $this->assertEquals(2, $query->get()->count());
        $this->assertEquals(2, $query->count());
        $this->assertCount(2, $query->cursorPaginate()->items());
    }

    public function testPaginationWithMultipleWhereClauses()
    {
        for ($i = 1; $i <= 4; $i++) {
            TestUser::create();
            TestPost::create(['title' => 'Hello world', 'user_id' => null]);
            TestPost::create(['title' => 'Goodbye world', 'user_id' => 2]);
            TestPost::create(['title' => 'Howdy', 'user_id' => 3]);
            TestPost::create(['title' => 'Howdy', 'user_id' => 4]);
        }

        $query = TestUser::query()->whereExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('test_posts')
                ->whereColumn('test_posts.user_id', 'test_users.id');
        })->whereHas('posts', function ($query) {
            $query->where('title', 'Howdy');
        })->where('id', '<', 5)->orderBy('id');

        $clonedQuery = $query->clone();
        $anotherQuery = $query->clone();

        $this->assertEquals(2, $query->get()->count());
        $this->assertEquals(2, $query->count());
        $this->assertCount(2, $query->cursorPaginate()->items());
        $this->assertCount(1, $clonedQuery->cursorPaginate(1)->items());
        $this->assertCount(
            1,
            $anotherQuery->cursorPaginate(5, ['*'], 'cursor', new Cursor(['id' => 3]))
                        ->items()
        );
    }

    public function testPaginationWithAliasedOrderBy()
    {
        for ($i = 1; $i <= 6; $i++) {
            TestUser::create();
        }

        $query = TestUser::query()->select('id as user_id')->orderBy('user_id');
        $clonedQuery = $query->clone();
        $anotherQuery = $query->clone();

        $this->assertEquals(6, $query->get()->count());
        $this->assertEquals(6, $query->count());
        $this->assertCount(6, $query->cursorPaginate()->items());
        $this->assertCount(3, $clonedQuery->cursorPaginate(3)->items());
        $this->assertCount(
            4,
            $anotherQuery->cursorPaginate(10, ['*'], 'cursor', new Cursor(['user_id' => 2]))
                        ->items()
        );
    }

    public function testPaginationWithDistinctColumnsAndSelect()
    {
        for ($i = 1; $i <= 3; $i++) {
            TestPost::create(['title' => 'Hello world']);
            TestPost::create(['title' => 'Goodbye world']);
        }

        $query = TestPost::query()->orderBy('title')->distinct('title')->select('title');

        $this->assertEquals(2, $query->get()->count());
        $this->assertEquals(2, $query->count());
        $this->assertCount(2, $query->cursorPaginate()->items());
    }

    public function testPaginationWithDistinctColumnsAndSelectAndJoin()
    {
        for ($i = 1; $i <= 5; $i++) {
            $user = TestUser::create();

            for ($j = 1; $j <= 10; $j++) {
                TestPost::create([
                    'title' => 'Title '.$i,
                    'user_id' => $user->id,
                ]);
            }
        }

        $query = TestUser::query()->join('test_posts', 'test_posts.user_id', '=', 'test_users.id')
            ->distinct('test_users.id')->select('test_users.*');

        $this->assertEquals(5, $query->get()->count());
        $this->assertEquals(5, $query->count());
        $this->assertCount(5, $query->cursorPaginate()->items());
    }
}

class TestPost extends Model
{
    protected $guarded = [];
}

class TestUser extends Model
{
    protected $guarded = [];

    public function posts()
    {
        return $this->hasMany(TestPost::class, 'user_id');
    }
}
