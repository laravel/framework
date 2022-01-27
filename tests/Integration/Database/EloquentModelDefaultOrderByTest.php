<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EloquentModelDefaultOrderByTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->string('slug');
        });
    }

    public function testUsesDefaultOrderByWhenNoOrderHasNotBeenSet()
    {
        $post1 = PostOrdered::create(['title' => 'Post 1', 'slug' => 'post-1']);
        $post2 = PostOrdered::create(['title' => 'Post 2', 'slug' => 'post-2']);
        $post3 = PostOrdered::create(['title' => 'Post 2', 'slug' => 'post-22']);

        $posts = PostOrdered::all();

        $this->assertCount(3, $posts);
        $this->assertTrue($posts->get(0)->is($post1));
        $this->assertTrue($posts->get(1)->is($post3));
        $this->assertTrue($posts->get(2)->is($post2));
    }

    public function testItDoesNotUseDefaultOrderByWhenOrderHasBeenSet()
    {
        $post1 = PostOrdered::create(['title' => 'Post 1', 'slug' => 'post-1']);
        $post2 = PostOrdered::create(['title' => 'Post 2', 'slug' => 'post-2']);
        $post3 = PostOrdered::create(['title' => 'Post 2', 'slug' => 'post-22']);

        $posts = PostOrdered::orderBy('id')->get();

        $this->assertCount(3, $posts);
        $this->assertTrue($posts->get(0)->is($post1));
        $this->assertTrue($posts->get(1)->is($post2));
        $this->assertTrue($posts->get(2)->is($post3));
    }

    public function testItDoesNotUseDefaultOrderByWhenWithoutDefaultOrderByIsUsed()
    {
        $this->assertStringNotContainsString('order by', PostOrdered::withoutDefaultOrderBy()->toSql());
    }

    public function testItDoesNotUseDefaultOrderByWhenOrderByIsAppliedViaScope()
    {
        $post1 = PostOrdered::create(['title' => 'Post 1', 'slug' => 'post-1']);
        $post2 = PostOrdered::create(['title' => 'Post 2', 'slug' => 'post-2']);
        $post3 = PostOrdered::create(['title' => 'Post 2', 'slug' => 'post-22']);

        $this->assertSame([1, 2, 3], PostOrdered::orderBySlugAscending()->pluck('id')->toArray());
    }

    public function testCursorRespectDefaultOrderBy()
    {
        $post1 = PostOrdered::create(['title' => 'Post 1', 'slug' => 'post-1']);
        $post2 = PostOrdered::create(['title' => 'Post 2', 'slug' => 'post-2']);
        $post3 = PostOrdered::create(['title' => 'Post 2', 'slug' => 'post-22']);

        $this->assertSame([1, 3, 2], PostOrdered::cursor()->pluck('id')->toArray());
    }

    public function testFirstRespectDefaultOrderBy()
    {
        PostOrdered::create(['title' => 'Test Post', 'slug' => 'post-1']);
        $expected = PostOrdered::create(['title' => 'Test Post', 'slug' => 'post-2']);

        $this->assertTrue(PostOrdered::first()->is($expected));
    }

    public function testValueRespectsDefaultOrderBy()
    {
        PostOrdered::create(['title' => 'Test Post', 'slug' => 'post-1']);
        PostOrdered::create(['title' => 'Test Post', 'slug' => 'post-2']);

        $this->assertSame('post-2', PostOrdered::value('slug'));
    }
}

class PostOrdered extends Model
{
    public $table = 'posts';
    public $timestamps = false;
    public $defaultOrderBy = [
        'title',
        'slug' => 'DESC',
    ];
    protected $guarded = [];

    public function scopeOrderBySlugAscending($query)
    {
        return $query->orderBy('slug', 'asc');
    }
}
