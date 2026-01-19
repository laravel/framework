<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Pagination\Cursor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EloquentCursorPaginateTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('test_posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->timestamps();
        });

        Schema::create('test_users', function ($table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->timestamps();
        });
    }

    public function testCursorPaginationOnTopOfColumns()
    {
        for ($i = 1; $i <= 16; $i++) {
            $posts[] = [
                'title' => 'Title '.$i,
            ];
        }
        TestPost::fillAndInsert($posts);

        $this->assertCount(15, TestPost::cursorPaginate(15, ['id', 'title']));
    }

    public function testPaginationWithUnion()
    {
        TestPost::fillAndInsert([
            ['title' => 'Hello world', 'user_id' => 1],
            ['title' => 'Goodbye world', 'user_id' => 2],
            ['title' => 'Howdy', 'user_id' => 3],
            ['title' => '4th', 'user_id' => 4],
        ]);

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
            $posts[] = ['title' => 'Hello world'];
            $posts[] = ['title' => 'Goodbye world'];
        }
        TestPost::fillAndInsert($posts);

        $query = TestPost::query()->distinct();

        $this->assertEquals(6, $query->get()->count());
        $this->assertEquals(6, $query->count());
        $this->assertCount(6, $query->cursorPaginate()->items());
    }

    public function testPaginationWithWhereClause()
    {
        for ($i = 1; $i <= 3; $i++) {
            $posts[] = ['title' => 'Hello world', 'user_id' => null];
            $posts[] = ['title' => 'Goodbye world', 'user_id' => 2];
        }
        TestPost::fillAndInsert($posts);

        $query = TestPost::query()->whereNull('user_id');

        $this->assertEquals(3, $query->get()->count());
        $this->assertEquals(3, $query->count());
        $this->assertCount(3, $query->cursorPaginate()->items());
    }

    public function testPaginationWithHasClause()
    {
        TestUser::fillAndInsert([[], [], []]);

        for ($i = 1; $i <= 3; $i++) {
            $posts[] = ['title' => 'Hello world', 'user_id' => null];
            $posts[] = ['title' => 'Goodbye world', 'user_id' => 2];
            $posts[] = ['title' => 'Howdy', 'user_id' => 3];
        }
        TestPost::fillAndInsert($posts);

        $query = TestUser::query()->has('posts');

        $this->assertEquals(2, $query->get()->count());
        $this->assertEquals(2, $query->count());
        $this->assertCount(2, $query->cursorPaginate()->items());
    }

    public function testPaginationWithWhereHasClause()
    {
        TestUser::fillAndInsert([[], [], []]);
        for ($i = 1; $i <= 3; $i++) {
            $posts[] = ['title' => 'Hello world', 'user_id' => null];
            $posts[] = ['title' => 'Goodbye world', 'user_id' => 2];
            $posts[] = ['title' => 'Howdy', 'user_id' => 3];
        }
        TestPost::fillAndInsert($posts);

        $query = TestUser::query()->whereHas('posts', function ($query) {
            $query->where('title', 'Howdy');
        });

        $this->assertEquals(1, $query->get()->count());
        $this->assertEquals(1, $query->count());
        $this->assertCount(1, $query->cursorPaginate()->items());
    }

    public function testPaginationWithWhereExistsClause()
    {
        TestUser::fillAndInsert([[], [], []]);
        for ($i = 1; $i <= 3; $i++) {
            $posts[] = ['title' => 'Hello world', 'user_id' => null];
            $posts[] = ['title' => 'Goodbye world', 'user_id' => 2];
            $posts[] = ['title' => 'Howdy', 'user_id' => 3];
        }
        TestPost::fillAndInsert($posts);

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
        TestUser::fillAndInsert([[], [], [], []]);
        for ($i = 1; $i <= 4; $i++) {
            $posts[] = ['title' => 'Hello world', 'user_id' => null];
            $posts[] = ['title' => 'Goodbye world', 'user_id' => 2];
            $posts[] = ['title' => 'Howdy', 'user_id' => 3];
            $posts[] = ['title' => 'Howdy', 'user_id' => 4];
        }
        TestPost::fillAndInsert($posts);

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

    public function testPaginationWithMultipleUnionAndMultipleWhereClauses()
    {
        TestPost::fillAndInsert([
            ['title' => 'Post A', 'user_id' => 100],
            ['title' => 'Post B', 'user_id' => 101],
        ]);

        $table1 = TestPost::select(['id', 'title', 'user_id'])->where('user_id', 100);
        $table2 = TestPost::select(['id', 'title', 'user_id'])->where('user_id', 101);
        $table3 = TestPost::select(['id', 'title', 'user_id'])->where('user_id', 101);

        $columns = ['id'];
        $cursorName = 'cursor-name';
        $cursor = new Cursor(['id' => 1]);

        $result = $table1->toBase()
            ->union($table2->toBase())
            ->union($table3->toBase())
            ->orderBy('id', 'asc')
            ->cursorPaginate(1, $columns, $cursorName, $cursor);

        $this->assertSame(['id'], $result->getOptions()['parameters']);

        $postB = $table2->where('id', '>', 1)->first();
        $this->assertEquals('Post B', $postB->title, 'Expect `Post B` is the result of the second query');

        $this->assertCount(1, $result->items(), 'Expect cursor paginated query should have 1 result');
        $this->assertEquals('Post B', current($result->items())->title, 'Expect the paginated query would return `Post B`');
    }

    public function testPaginationWithMultipleAliases()
    {
        TestUser::fillAndInsert([
            ['name' => 'A (user)'],
            ['name' => 'C (user)'],
        ]);

        TestPost::fillAndInsert([['title' => 'B (post)'], ['title' => 'D (post)']]);

        $table1 = TestPost::select(['title as alias']);
        $table2 = TestUser::select(['name as alias']);

        $columns = ['alias'];
        $cursorName = 'cursor-name';
        $cursor = new Cursor(['alias' => 'A (user)']);

        $result = $table1->toBase()
            ->union($table2->toBase())
            ->orderBy('alias', 'asc')
            ->cursorPaginate(1, $columns, $cursorName, $cursor);

        $this->assertSame(['alias'], $result->getOptions()['parameters']);

        $this->assertCount(1, $result->items(), 'Expect cursor paginated query should have 1 result');
        $this->assertEquals('B (post)', current($result->items())->alias, 'Expect the paginated query would return `B (post)`');
    }

    public function testPaginationWithAliasedOrderBy()
    {
        TestUser::fillAndInsert([[], [], [], [], [], []]);

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
            $posts[] = ['title' => 'Hello world'];
            $posts[] = ['title' => 'Goodbye world'];
        }
        TestPost::fillAndInsert($posts);

        $query = TestPost::query()->orderBy('title')->distinct('title')->select('title');

        $this->assertEquals(2, $query->get()->count());
        $this->assertEquals(2, $query->count());
        $this->assertCount(2, $query->cursorPaginate()->items());
    }

    public function testPaginationWithDistinctColumnsAndSelectAndJoin()
    {
        TestUser::fillAndInsert([[], [], [], [], []]);
        $users = TestUser::query()->get();
        for ($i = 1; $i <= 5; $i++) {
            $user = $users[$i - 1];

            for ($j = 1; $j <= 10; $j++) {
                $posts[] = [
                    'title' => 'Title '.$i,
                    'user_id' => $user->id,
                ];
            }
        }
        TestPost::fillAndInsert($posts);

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
