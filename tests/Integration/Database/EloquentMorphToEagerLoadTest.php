<?php

namespace Illuminate\Tests\Integration\Database\EloquentMorphToEagerLoadTest;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\App\Enums\ArticleSlug;
use Illuminate\Tests\App\Models\Relationships\Comment;
use Illuminate\Tests\App\Models\Relationships\MorphToEagerLoadArticle as Article;
use Illuminate\Tests\App\Models\Relationships\MorphToEagerLoadPost as Post;
use Illuminate\Tests\App\Models\Relationships\MorphToEagerLoadVideo as Video;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class EloquentMorphToEagerLoadTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
        });

        Schema::create('articles', function (Blueprint $table) {
            $table->string('slug')->primary();
        });

        Schema::create('videos', function (Blueprint $table) {
            $table->string('id')->primary();
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('commentable_type');
            $table->string('commentable_id');
        });

        $post = Post::create();
        $article = Article::create(['slug' => ArticleSlug::Review->value]);
        $video = Video::create(['id' => '550e8400-e29b-41d4-a716-446655440000']);

        (new Comment)->commentable()->associate($post)->save();
        (new Comment)->commentable()->associate($article)->save();

        $comment = new Comment;
        $comment->commentable_type = Video::class;
        $comment->commentable_id = (string) $video->id;
        $comment->save();
    }

    public function testEagerLoadingResolvesRelationWithPrimitivePrimaryKey(): void
    {
        $comments = Comment::with('commentable')
            ->where('commentable_type', Post::class)
            ->get();

        $this->assertNotNull($comments[0]->commentable);
        $this->assertInstanceOf(Post::class, $comments[0]->commentable);
    }

    public function testEagerLoadingResolvesRelationWithBackedEnumPrimaryKey(): void
    {
        $comments = Comment::with('commentable')
            ->where('commentable_type', Article::class)
            ->get();

        $this->assertNotNull($comments[0]->commentable);
        $this->assertInstanceOf(Article::class, $comments[0]->commentable);
        $this->assertSame(ArticleSlug::Review, $comments[0]->commentable->slug);
    }

    public function testEagerLoadingResolvesRelationWithUuidValueObjectPrimaryKey(): void
    {
        $comments = Comment::with('commentable')
            ->where('commentable_type', Video::class)
            ->get();

        $this->assertNotNull($comments[0]->commentable);
        $this->assertInstanceOf(Video::class, $comments[0]->commentable);
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', (string) $comments[0]->commentable->id);
    }
}
