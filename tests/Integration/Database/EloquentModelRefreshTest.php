<?php

namespace Illuminate\Tests\Integration\Database\EloquentModelRefreshTest;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\App\Models\Scopes\AsPivotPost;
use Illuminate\Tests\App\Models\Scopes\SoftDeletingGlobalScopePost as Post;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class EloquentModelRefreshTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
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

    public function testItDoesNotSyncPreviousOnRefresh()
    {
        $post = Post::create(['title' => 'pat']);

        Post::find($post->id)->update(['title' => 'patrick']);

        $post->refresh();

        $this->assertEmpty($post->getDirty());
        $this->assertEmpty($post->getPrevious());
    }

    public function testItRefreshesModelForUpdate()
    {
        $post = Post::create(['title' => 'pat']);

        Post::whereKey($post)->update(['title' => 'patrick']);

        DB::transaction(function () use ($post) {
            $this->assertSame($post, $post->refreshForUpdate());
        });

        $this->assertSame('patrick', $post->title);
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
}
