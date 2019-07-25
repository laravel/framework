<?php

namespace Illuminate\Tests\Integration\Database\EloquentPaginateTest;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

/**
 * @group integration
 */
class EloquentPaginateTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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

    public function test_pagination_on_top_of_columns()
    {
        for ($i = 1; $i <= 50; $i++) {
            Post::create([
                'title' => 'Title '.$i,
            ]);
        }

        $this->assertCount(15, Post::paginate(15, ['id', 'title']));
    }

    public function test_pagination_with_distinct()
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

    public function test_pagination_with_distinct_and_select()
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

    public function test_pagination_with_distinct_columns_and_select()
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

    public function test_pagination_with_distinct_columns_and_select_and_join()
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
}

class Post extends Model
{
    protected $guarded = [];
}

class User extends Model
{
    protected $guarded = [];
}
