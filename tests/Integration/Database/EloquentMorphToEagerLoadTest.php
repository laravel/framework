<?php

namespace Illuminate\Tests\Integration\Database\EloquentMorphToEagerLoadTest;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
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

enum ArticleSlug: string
{
    case Review = 'review';
}

class Post extends Model
{
    public $timestamps = false;
}

class Article extends Model
{
    public $timestamps = false;

    protected $primaryKey = 'slug';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $casts = ['slug' => ArticleSlug::class];

    protected $fillable = ['slug'];
}

class Comment extends Model
{
    public $timestamps = false;

    public function commentable()
    {
        return $this->morphTo();
    }
}

class Uuid
{
    public function __construct(private readonly string $value)
    {
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

class UuidCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return new Uuid($value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return (string) $value;
    }
}

class Video extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $fillable = ['id'];

    protected $keyType = 'string';

    protected $casts = ['id' => UuidCast::class];
}
