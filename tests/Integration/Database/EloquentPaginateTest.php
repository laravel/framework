<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EloquentPaginateTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function ($table) {
            $table->increments('id');
            $table->timestamps();
        });
    }

    public function testPaginationOnTopOfColumns()
    {
        for ($i = 1; $i <= 50; $i++) {
            Post::create([
                'title' => 'Title '.$i,
            ]);
        }

        $this->assertCount(15, Post::paginate(15, ['id', 'title']));
    }

    public function testPaginationWithDistinct()
    {
        for ($i = 1; $i <= 3; $i++) {
            Post::create(['title' => 'Hello world']);
            Post::create(['title' => 'Goodbye world']);
        }

        $query = Post::query()->distinct();

        $this->assertEquals(6, $query->get()->count());
        $this->assertEquals(6, $query->count());
        $this->assertEquals(6, $query->paginate()->total());
    }

    public function testPaginationWithDistinctAndSelect()
    {
        // This is the 'broken' behaviour, but this test is added to show backwards compatibility.
        for ($i = 1; $i <= 3; $i++) {
            Post::create(['title' => 'Hello world']);
            Post::create(['title' => 'Goodbye world']);
        }

        $query = Post::query()->distinct()->select('title');

        $this->assertEquals(2, $query->get()->count());
        $this->assertEquals(6, $query->count());
        $this->assertEquals(6, $query->paginate()->total());
    }

    public function testPaginationWithDistinctColumnsAndSelect()
    {
        for ($i = 1; $i <= 3; $i++) {
            Post::create(['title' => 'Hello world']);
            Post::create(['title' => 'Goodbye world']);
        }

        $query = Post::query()->distinct('title')->select('title');

        $this->assertEquals(2, $query->get()->count());
        $this->assertEquals(2, $query->count());
        $this->assertEquals(2, $query->paginate()->total());
    }

    public function testPaginationWithDistinctColumnsAndSelectAndJoin()
    {
        for ($i = 1; $i <= 5; $i++) {
            $user = User::create();
            for ($j = 1; $j <= 10; $j++) {
                Post::create([
                    'title' => 'Title '.$i,
                    'user_id' => $user->id,
                ]);
            }
        }

        $query = User::query()->join('posts', 'posts.user_id', '=', 'users.id')
            ->distinct('users.id')->select('users.*');

        $this->assertEquals(5, $query->get()->count());
        $this->assertEquals(5, $query->count());
        $this->assertEquals(5, $query->paginate()->total());
    }

    public function testPaginateUsingWindowBasicFunctionality()
    {
        for ($i = 1; $i <= 50; $i++) {
            Post::create([
                'title' => 'Title '.$i,
            ]);
        }

        $paginator = Post::paginateUsingWindow(15);

        $this->assertCount(15, $paginator);
        $this->assertEquals(50, $paginator->total());
        $this->assertEquals(4, $paginator->lastPage());
        $this->assertEquals(1, $paginator->currentPage());
    }

    public function testPaginateUsingWindowWithCustomColumns()
    {
        for ($i = 1; $i <= 30; $i++) {
            Post::create([
                'title' => 'Title '.$i,
            ]);
        }

        $paginator = Post::paginateUsingWindow(10, ['id', 'title']);

        $this->assertCount(10, $paginator);
        $this->assertEquals(30, $paginator->total());
        $this->assertEquals(3, $paginator->lastPage());

        // Verify only specified columns are returned (plus total_count is removed)
        $firstItem = $paginator->items()[0];
        $this->assertTrue(isset($firstItem->id));
        $this->assertTrue(isset($firstItem->title));
        $this->assertFalse(property_exists($firstItem, 'created_at'));
        $this->assertFalse(property_exists($firstItem, 'total_count'));
    }

    public function testPaginateUsingWindowWithStringColumn()
    {
        for ($i = 1; $i <= 25; $i++) {
            Post::create([
                'title' => 'Title '.$i,
            ]);
        }

        $paginator = Post::paginateUsingWindow(10, 'title');

        $this->assertCount(10, $paginator);
        $this->assertEquals(25, $paginator->total());

        $firstItem = $paginator->items()[0];
        $this->assertTrue(isset($firstItem->title));
        $this->assertFalse(property_exists($firstItem, 'total_count'));
    }

    public function testPaginateUsingWindowWithSpecificPage()
    {
        for ($i = 1; $i <= 50; $i++) {
            Post::create([
                'title' => 'Title '.$i,
            ]);
        }

        $paginator = Post::paginateUsingWindow(15, ['*'], 'page', 2);

        $this->assertCount(15, $paginator);
        $this->assertEquals(50, $paginator->total());
        $this->assertEquals(2, $paginator->currentPage());
        $this->assertEquals(4, $paginator->lastPage());
    }

    public function testPaginateUsingWindowWithEmptyResults()
    {
        $paginator = Post::where('id', '<', 0)->paginateUsingWindow(15);

        $this->assertCount(0, $paginator);
        $this->assertEquals(0, $paginator->total());
        $this->assertEquals(1, $paginator->lastPage());
        $this->assertEquals(1, $paginator->currentPage());
    }

    public function testPaginateUsingWindowWithWhereCondition()
    {
        for ($i = 1; $i <= 30; $i++) {
            Post::create([
                'title' => 'Title '.$i,
            ]);
        }

        $paginator = Post::where('id', '>', 10)->paginateUsingWindow(10);

        $this->assertCount(10, $paginator);
        $this->assertEquals(20, $paginator->total());
        $this->assertEquals(2, $paginator->lastPage());
    }

    public function testPaginateUsingWindowWithOrderBy()
    {
        for ($i = 1; $i <= 20; $i++) {
            Post::create([
                'title' => 'Title '.$i,
            ]);
        }

        $paginator = Post::orderBy('id', 'desc')->paginateUsingWindow(5);

        $this->assertCount(5, $paginator);
        $this->assertEquals(20, $paginator->total());

        $items = $paginator->items();
        $this->assertEquals(20, $items[0]->id);
        $this->assertEquals(19, $items[1]->id);
    }

    public function testPaginateUsingWindowWithJoin()
    {
        for ($i = 1; $i <= 5; $i++) {
            $user = User::create();
            for ($j = 1; $j <= 4; $j++) {
                Post::create([
                    'title' => 'Title '.$i.'-'.$j,
                    'user_id' => $user->id,
                ]);
            }
        }

        $paginator = Post::join('users', 'posts.user_id', '=', 'users.id')
            ->paginateUsingWindow(10, ['posts.*', 'users.id as user_id']);

        $this->assertCount(10, $paginator);
        $this->assertEquals(20, $paginator->total());
        $this->assertEquals(2, $paginator->lastPage());
    }

    public function testPaginateUsingWindowPerformanceComparison()
    {
        // Create a larger dataset to test performance benefit
        for ($i = 1; $i <= 100; $i++) {
            Post::create([
                'title' => 'Title '.$i,
            ]);
        }

        // Test that both methods return the same results
        $standardPaginator = Post::paginate(20);
        $windowPaginator = Post::paginateUsingWindow(20);

        $this->assertEquals($standardPaginator->total(), $windowPaginator->total());
        $this->assertEquals($standardPaginator->lastPage(), $windowPaginator->lastPage());
        $this->assertEquals($standardPaginator->currentPage(), $windowPaginator->currentPage());
        $this->assertCount(20, $standardPaginator);
        $this->assertCount(20, $windowPaginator);
    }
}

class Post extends Model
{
    protected $guarded = [];
}

class User extends Model
{
    protected $guarded = [];
}
