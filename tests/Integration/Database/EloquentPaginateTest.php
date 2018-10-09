<?php

namespace Illuminate\Tests\Integration\Database\EloquentPaginateTest;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

/**
 * @group integration
 */
class EloquentPaginateTest extends DatabaseTestCase
{
    public function setUp()
    {
        parent::setUp();

        Schema::create('posts', function ($table) {
            $table->increments('id');
            $table->string('title')->nullable();
            $table->timestamps();
        });
        Schema::create('post_comments', function ($table) {
            $table->increments('id');
            $table->unsignedInteger('post_id');
            $table->foreign('post_id')->references('id')->on('posts');
            $table->string('text')->nullable();
            $table->unsignedInteger('likes')->default(0);
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
        for ($i = 1; $i <= 25; $i++) {
            $post = Post::create([
                'title' => 'Title '.$i,
            ]);

            for ($j = 1; $j <= 5; $j++) {
                PostComment::create([
                    'post_id' => $post->id,
                    'text'    => 'Comment '.$j,
                    'likes'   => $i + $j,
                ]);
            }
        }

        $paginator = Post::join('post_comments', 'post_comments.post_id', '=', 'posts.id')
                         ->where('likes', '>', 10)
                         ->distinct()
                         ->select('posts.*')
                         ->paginate(15);

        $this->assertCount(15, $paginator);
        $this->assertEquals(20, $paginator->total());
    }
}

class Post extends Model
{
    protected $guarded = [];
}

class PostComment extends Model
{
    protected $guarded = [];
}
