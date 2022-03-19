<?php

namespace Illuminate\Tests\Integration\Database\EloquentModelRefreshTest;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Concerns\AsPivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class EloquentModelRefreshTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
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
            $table->increments('id');
            $table->bigInteger('foreign_id');
            $table->bigInteger('related_id');
        });

        $post = AsPivotPost::create(['title' => 'parent']);
        $child = AsPivotPost::create(['title' => 'child']);

        $post->children()->attach($child->getKey());

        $this->assertEquals(1, $post->children->count());

        $post->children->first()->refresh();
    }

    public function testItUnsetsRelations()
    {
        Schema::create('post_children', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->bigInteger('parent_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $parent = Post::create(['title' => 'parent']);
        $child = ChildPost::create(['title' => 'child']);

        $child->parent()->associate($parent)->save();

        $this->assertInstanceOf(
            Post::class,
            $child->parent
        );

        $child->parent()->disassociate($parent)->save();
        $child->refresh();

        $this->assertArrayNotHasKey(
            'parent',
            $child->getRelations(),
            'The parent relation was not unset by calling refresh()'
        );
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
}

class ChildPost extends Post
{
    public $table = 'post_children';

    public function parent()
    {
        return $this->belongsTo(static::class, 'parent_id', 'id');
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


