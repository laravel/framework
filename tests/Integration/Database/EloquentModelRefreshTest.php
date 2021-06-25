<?php

namespace Illuminate\Tests\Integration\Database\EloquentModelRefreshTest;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Concerns\AsPivot;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

/**
 * @group integration
 */
class EloquentModelRefreshTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->morphs('commentable');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function testItRefreshesModelExcludedByGlobalScope()
    {
        $post = Post::create(['title' => 'mohamed']);

        $post->refresh();
    }

    public function testItRefreshesASoftDeletedModel()
    {
        $post = Post::create(['title' => 'said']);

        Post::find($post->id)->delete();

        $this->assertFalse($post->trashed());

        $post->refresh();

        $this->assertTrue($post->trashed());
    }

    public function testItSyncsOriginalOnRefresh()
    {
        $post = Post::create(['title' => 'pat']);

        Post::find($post->id)->update(['title' => 'patrick']);

        $post->refresh();

        $this->assertEmpty($post->getDirty());

        $this->assertSame('patrick', $post->getOriginal('title'));
    }

    public function testAsPivot()
    {
        Schema::create('post_posts', function (Blueprint $table) {
            $table->bigInteger('foreign_id');
            $table->bigInteger('related_id');
        });

        $post = AsPivotPost::create(['title' => 'parent']);
        $child = AsPivotPost::create(['title' => 'child']);

        $post->children()->attach($child->getKey());

        $this->assertEquals(1, $post->children->count());

        $post->children->first()->refresh();
    }

    public function testMorph()
    {
        $post = Post::create(['title' => 'pat']);
        $post = Post::with('comments', 'latestComment')->find($post->id);
        $firstComment = Comment::create(['commentable_type' => Post::class, 'commentable_id' => $post->id]);

        $this->assertEquals(0, $post->comments->count());

        $post->refresh();

        $this->assertEquals(1, $post->comments->count());
        $this->assertEquals(1, $post->latestComment->id);

        $secondComment = Comment::create(['commentable_type' => Post::class, 'commentable_id' => $post->id]);

        $post->refresh();

        $this->assertEquals(2, $post->comments->count());
        $this->assertEquals(2, $post->latestComment->id);
    }
}

class Post extends Model
{
    public $table = 'posts';
    public $timestamps = true;
    protected $guarded = [];

    use SoftDeletes;

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('age', function ($query) {
            $query->where('title', '!=', 'mohamed');
        });
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function latestComment()
    {
        return $this->morphOne(Comment::class, 'commentable')->ofMany(['id' => 'max']);
    }
}

class Comment extends MorphPivot
{
    public $table = 'comments';
    protected $guarded = [];

    public function commentable()
    {
        return $this->morphTo();
    }
}

class AsPivotPost extends Post
{
    public function children()
    {
        return $this
            ->belongsToMany(static::class, (new AsPivotPostPivot)->getTable(), 'foreign_id', 'related_id')
            ->using(AsPivotPostPivot::class);
    }
}

class AsPivotPostPivot extends Model
{
    use AsPivot;

    protected $table = 'post_posts';
}
