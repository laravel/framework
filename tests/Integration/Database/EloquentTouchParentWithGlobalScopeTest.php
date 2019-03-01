<?php

namespace Illuminate\Tests\Integration\Database\EloquentTouchParentWithGlobalScopeTest;

use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

/**
 * @group integration
 */
class EloquentTouchParentWithGlobalScopeTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('post_id');
            $table->string('title');
            $table->timestamps();
        });

        Carbon::setTestNow(null);
    }

    public function test_basic_create_and_retrieve()
    {
        $post = Post::create(['title' => Str::random(), 'updated_at' => '2016-10-10 10:10:10']);

        $this->assertEquals('2016-10-10', $post->fresh()->updated_at->toDateString());

        $post->comments()->create(['title' => Str::random()]);

        $this->assertNotEquals('2016-10-10', $post->fresh()->updated_at->toDateString());
    }
}

class Post extends Model
{
    public $table = 'posts';
    public $timestamps = true;
    protected $guarded = ['id'];

    public function comments()
    {
        return $this->hasMany(Comment::class, 'post_id');
    }

    public static function boot()
    {
        parent::boot();

        static::addGlobalScope('age', function ($builder) {
            $builder->join('comments', 'comments.post_id', '=', 'posts.id');
        });
    }
}

class Comment extends Model
{
    public $table = 'comments';
    public $timestamps = true;
    protected $guarded = ['id'];
    protected $touches = ['post'];

    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }
}
