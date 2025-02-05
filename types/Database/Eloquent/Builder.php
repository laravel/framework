<?php

namespace Illuminate\Types\Builder;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\HasBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Query\Builder as QueryBuilder;

use function PHPStan\Testing\assertType;

/** @param \Illuminate\Database\Eloquent\Builder<User> $query */
function test(
    Builder $query,
    User $user,
    Post $post,
    ChildPost $childPost,
    Comment $comment,
    QueryBuilder $queryBuilder
): void {
    assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Types\Builder\User>', $query->where('id', 1));
    assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Types\Builder\User>', $query->orWhere('name', 'John'));
    assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Types\Builder\User>', $query->whereNot('status', 'active'));
    assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Types\Builder\User>', $query->with('relation'));
    assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Types\Builder\User>', $query->with(['relation' => ['foo' => fn ($q) => $q]]));
    assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Types\Builder\User>', $query->with(['relation' => function ($query) {
        // assertType('Illuminate\Database\Eloquent\Relations\Relation<*,*,*>', $query);
    }]));
    assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Types\Builder\User>', $query->without('relation'));
    assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Types\Builder\User>', $query->withOnly(['relation']));
    assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Types\Builder\User>', $query->withOnly(['relation' => ['foo' => fn ($q) => $q]]));
    assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Types\Builder\User>', $query->withOnly(['relation' => function ($query) {
        // assertType('Illuminate\Database\Eloquent\Relations\Relation<*,*,*>', $query);
    }]));
    assertType('array<int, Illuminate\Types\Builder\User>', $query->getModels());
    assertType('array<int, Illuminate\Types\Builder\User>', $query->eagerLoadRelations([]));
    assertType('Illuminate\Database\Eloquent\Collection<int, Illuminate\Types\Builder\User>', $query->get());
    assertType('Illuminate\Database\Eloquent\Collection<int, Illuminate\Types\Builder\User>', $query->hydrate([]));
    assertType('Illuminate\Database\Eloquent\Collection<int, Illuminate\Types\Builder\User>', $query->fromQuery('foo', []));
    assertType('Illuminate\Database\Eloquent\Collection<int, Illuminate\Types\Builder\User>', $query->findMany([1, 2, 3]));
    assertType('Illuminate\Database\Eloquent\Collection<int, Illuminate\Types\Builder\User>', $query->findOrFail([1]));
    assertType('Illuminate\Database\Eloquent\Collection<int, Illuminate\Types\Builder\User>', $query->findOrNew([1]));
    assertType('Illuminate\Database\Eloquent\Collection<int, Illuminate\Types\Builder\User>', $query->find([1]));
    assertType('Illuminate\Database\Eloquent\Collection<int, Illuminate\Types\Builder\User>', $query->findOr([1], callback: fn () => 42));
    assertType('Illuminate\Types\Builder\User', $query->findOrFail(1));
    assertType('Illuminate\Types\Builder\User|null', $query->find(1));
    assertType('42|Illuminate\Types\Builder\User', $query->findOr(1, fn () => 42));
    assertType('42|Illuminate\Types\Builder\User', $query->findOr(1, callback: fn () => 42));
    assertType('Illuminate\Types\Builder\User|null', $query->first());
    assertType('42|Illuminate\Types\Builder\User', $query->firstOr(fn () => 42));
    assertType('42|Illuminate\Types\Builder\User', $query->firstOr(callback: fn () => 42));
    assertType('Illuminate\Types\Builder\User', $query->firstOrNew(['id' => 1]));
    assertType('Illuminate\Types\Builder\User', $query->findOrNew(1));
    assertType('Illuminate\Types\Builder\User', $query->firstOrCreate(['id' => 1]));
    assertType('Illuminate\Types\Builder\User', $query->create(['name' => 'John']));
    assertType('Illuminate\Types\Builder\User', $query->forceCreate(['name' => 'John']));
    assertType('Illuminate\Types\Builder\User', $query->forceCreateQuietly(['name' => 'John']));
    assertType('Illuminate\Types\Builder\User', $query->getModel());
    assertType('Illuminate\Types\Builder\User', $query->make(['name' => 'John']));
    assertType('Illuminate\Types\Builder\User', $query->forceCreate(['name' => 'John']));
    assertType('Illuminate\Types\Builder\User', $query->updateOrCreate(['id' => 1], ['name' => 'John']));
    assertType('Illuminate\Types\Builder\User', $query->firstOrFail());
    assertType('Illuminate\Types\Builder\User', $query->sole());
    assertType('Illuminate\Support\LazyCollection<int, Illuminate\Types\Builder\User>', $query->cursor());
    assertType('Illuminate\Support\LazyCollection<int, Illuminate\Types\Builder\User>', $query->cursor());
    assertType('Illuminate\Support\LazyCollection<int, Illuminate\Types\Builder\User>', $query->lazy());
    assertType('Illuminate\Support\LazyCollection<int, Illuminate\Types\Builder\User>', $query->lazyById());
    assertType('Illuminate\Support\LazyCollection<int, Illuminate\Types\Builder\User>', $query->lazyByIdDesc());
    assertType('Illuminate\Support\Collection<(int|string), mixed>', $query->pluck('foo'));
    assertType('Illuminate\Database\Eloquent\Relations\Relation<Illuminate\Database\Eloquent\Model, Illuminate\Types\Builder\User, *>', $query->getRelation('foo'));
    assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Types\Builder\Post>', $query->setModel(new Post()));

    assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Types\Builder\User>', $query->has('foo', callback: function ($query) {
        assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Database\Eloquent\Model>', $query);
    }));
    assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Types\Builder\User>', $query->has($user->posts(), callback: function ($query) {
        assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Types\Builder\Post>', $query);
    }));
    assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Types\Builder\User>', $query->orHas($user->posts()));
    assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Types\Builder\User>', $query->doesntHave($user->posts(), callback: function ($query) {
        assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Types\Builder\Post>', $query);
    }));
    assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Types\Builder\User>', $query->orDoesntHave($user->posts()));
    assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Types\Builder\User>', $query->whereHas($user->posts(), function ($query) {
        assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Types\Builder\Post>', $query);
    }));
    assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Types\Builder\User>', $query->withWhereHas('posts', function ($query) {
        assertType('Illuminate\Database\Eloquent\Builder<*>|Illuminate\Database\Eloquent\Relations\Relation<*, *, *>', $query);
    }));
    assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Types\Builder\User>', $query->orWhereHas($user->posts(), function ($query) {
        assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Types\Builder\Post>', $query);
    }));
    assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Types\Builder\User>', $query->whereDoesntHave($user->posts(), function ($query) {
        assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Types\Builder\Post>', $query);
    }));
    assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Types\Builder\User>', $query->orWhereDoesntHave($user->posts(), function ($query) {
        assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Types\Builder\Post>', $query);
    }));
    assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Types\Builder\User>', $query->hasMorph($post->taggable(), 'taggable', callback: function ($query, $type) {
        assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Database\Eloquent\Model>', $query);
        assertType('string', $type);
    }));
    assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Types\Builder\User>', $query->orHasMorph($post->taggable(), 'taggable'));
    assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Types\Builder\User>', $query->doesntHaveMorph($post->taggable(), 'taggable', callback: function ($query, $type) {
        assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Database\Eloquent\Model>', $query);
        assertType('string', $type);
    }));
    assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Types\Builder\User>', $query->orDoesntHaveMorph($post->taggable(), 'taggable'));
    assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Types\Builder\User>', $query->whereHasMorph($post->taggable(), 'taggable', function ($query, $type) {
        assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Database\Eloquent\Model>', $query);
        assertType('string', $type);
    }));
    assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Types\Builder\User>', $query->orWhereHasMorph($post->taggable(), 'taggable', function ($query, $type) {
        assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Database\Eloquent\Model>', $query);
        assertType('string', $type);
    }));
    assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Types\Builder\User>', $query->whereDoesntHaveMorph($post->taggable(), 'taggable', function ($query, $type) {
        assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Database\Eloquent\Model>', $query);
        assertType('string', $type);
    }));
    assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Types\Builder\User>', $query->orWhereDoesntHaveMorph($post->taggable(), 'taggable', function ($query, $type) {
        assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Database\Eloquent\Model>', $query);
        assertType('string', $type);
    }));
    assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Types\Builder\User>', $query->whereRelation($user->posts(), function ($query) {
        assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Types\Builder\Post>', $query);
    }));
    assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Types\Builder\User>', $query->orWhereRelation($user->posts(), function ($query) {
        assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Types\Builder\Post>', $query);
    }));
    assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Types\Builder\User>', $query->whereDoesntHaveRelation($user->posts(), function ($query) {
        assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Types\Builder\Post>', $query);
    }));
    assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Types\Builder\User>', $query->orWhereDoesntHaveRelation($user->posts(), function ($query) {
        assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Types\Builder\Post>', $query);
    }));
    assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Types\Builder\User>', $query->whereMorphRelation($post->taggable(), 'taggable', function ($query) {
        assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Database\Eloquent\Model>', $query);
    }));
    assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Types\Builder\User>', $query->orWhereMorphRelation($post->taggable(), 'taggable', function ($query) {
        assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Database\Eloquent\Model>', $query);
    }));
    assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Types\Builder\User>', $query->whereMorphDoesntHaveRelation($post->taggable(), 'taggable', function ($query) {
        assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Database\Eloquent\Model>', $query);
    }));
    assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Types\Builder\User>', $query->orWhereMorphDoesntHaveRelation($post->taggable(), 'taggable', function ($query) {
        assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Database\Eloquent\Model>', $query);
    }));
    assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Types\Builder\User>', $query->whereMorphedTo($post->taggable(), new Post()));
    assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Types\Builder\User>', $query->whereNotMorphedTo($post->taggable(), new Post()));
    assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Types\Builder\User>', $query->orWhereMorphedTo($post->taggable(), new Post()));
    assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Types\Builder\User>', $query->orWhereNotMorphedTo($post->taggable(), new Post()));

    $query->chunk(1, function ($users, $page) {
        assertType('Illuminate\Support\Collection<int, Illuminate\Types\Builder\User>', $users);
        assertType('int', $page);
    });
    $query->chunkById(1, function ($users, $page) {
        assertType('Illuminate\Support\Collection<int, Illuminate\Types\Builder\User>', $users);
        assertType('int', $page);
    });
    $query->chunkMap(function ($users) {
        assertType('Illuminate\Types\Builder\User', $users);
    });
    $query->chunkByIdDesc(1, function ($users, $page) {
        assertType('Illuminate\Support\Collection<int, Illuminate\Types\Builder\User>', $users);
        assertType('int', $page);
    });
    $query->each(function ($users, $page) {
        assertType('Illuminate\Types\Builder\User', $users);
        assertType('int', $page);
    });
    $query->eachById(function ($users, $page) {
        assertType('Illuminate\Types\Builder\User', $users);
        assertType('int', $page);
    });

    assertType('Illuminate\Types\Builder\CommonBuilder<Illuminate\Types\Builder\Post>', Post::query());
    assertType('Illuminate\Types\Builder\CommonBuilder<Illuminate\Types\Builder\Post>', Post::on());
    assertType('Illuminate\Types\Builder\CommonBuilder<Illuminate\Types\Builder\Post>', Post::onWriteConnection());
    assertType('Illuminate\Types\Builder\CommonBuilder<Illuminate\Types\Builder\Post>', Post::with([]));
    assertType('Illuminate\Types\Builder\CommonBuilder<Illuminate\Types\Builder\Post>', $post->newQuery());
    assertType('Illuminate\Types\Builder\CommonBuilder<Illuminate\Types\Builder\Post>', $post->newEloquentBuilder($queryBuilder));
    assertType('Illuminate\Types\Builder\CommonBuilder<Illuminate\Types\Builder\Post>', $post->newModelQuery());
    assertType('Illuminate\Types\Builder\CommonBuilder<Illuminate\Types\Builder\Post>', $post->newQueryWithoutRelationships());
    assertType('Illuminate\Types\Builder\CommonBuilder<Illuminate\Types\Builder\Post>', $post->newQueryWithoutScopes());
    assertType('Illuminate\Types\Builder\CommonBuilder<Illuminate\Types\Builder\Post>', $post->newQueryWithoutScope('foo'));
    assertType('Illuminate\Types\Builder\CommonBuilder<Illuminate\Types\Builder\Post>', $post->newQueryForRestoration(1));
    assertType('Illuminate\Types\Builder\CommonBuilder<Illuminate\Types\Builder\Post>', $post->newQuery()->where('foo', 'bar'));
    assertType('Illuminate\Types\Builder\CommonBuilder<Illuminate\Types\Builder\Post>', $post->newQuery()->foo());
    assertType('Illuminate\Types\Builder\Post', $post->newQuery()->create(['name' => 'John']));

    assertType('Illuminate\Types\Builder\CommonBuilder<Illuminate\Types\Builder\ChildPost>', ChildPost::query());
    assertType('Illuminate\Types\Builder\CommonBuilder<Illuminate\Types\Builder\ChildPost>', ChildPost::on());
    assertType('Illuminate\Types\Builder\CommonBuilder<Illuminate\Types\Builder\ChildPost>', ChildPost::onWriteConnection());
    assertType('Illuminate\Types\Builder\CommonBuilder<Illuminate\Types\Builder\ChildPost>', ChildPost::with([]));
    assertType('Illuminate\Types\Builder\CommonBuilder<Illuminate\Types\Builder\ChildPost>', $childPost->newQuery());
    assertType('Illuminate\Types\Builder\CommonBuilder<Illuminate\Types\Builder\ChildPost>', $childPost->newEloquentBuilder($queryBuilder));
    assertType('Illuminate\Types\Builder\CommonBuilder<Illuminate\Types\Builder\ChildPost>', $childPost->newModelQuery());
    assertType('Illuminate\Types\Builder\CommonBuilder<Illuminate\Types\Builder\ChildPost>', $childPost->newQueryWithoutRelationships());
    assertType('Illuminate\Types\Builder\CommonBuilder<Illuminate\Types\Builder\ChildPost>', $childPost->newQueryWithoutScopes());
    assertType('Illuminate\Types\Builder\CommonBuilder<Illuminate\Types\Builder\ChildPost>', $childPost->newQueryWithoutScope('foo'));
    assertType('Illuminate\Types\Builder\CommonBuilder<Illuminate\Types\Builder\ChildPost>', $childPost->newQueryForRestoration(1));
    assertType('Illuminate\Types\Builder\CommonBuilder<Illuminate\Types\Builder\ChildPost>', $childPost->newQuery()->where('foo', 'bar'));
    assertType('Illuminate\Types\Builder\CommonBuilder<Illuminate\Types\Builder\ChildPost>', $childPost->newQuery()->foo());
    assertType('Illuminate\Types\Builder\ChildPost', $childPost->newQuery()->create(['name' => 'John']));

    assertType('Illuminate\Types\Builder\CommentBuilder', Comment::query());
    assertType('Illuminate\Types\Builder\CommentBuilder', Comment::on());
    assertType('Illuminate\Types\Builder\CommentBuilder', Comment::onWriteConnection());
    assertType('Illuminate\Types\Builder\CommentBuilder', Comment::with([]));
    assertType('Illuminate\Types\Builder\CommentBuilder', $comment->newQuery());
    assertType('Illuminate\Types\Builder\CommentBuilder', $comment->newEloquentBuilder($queryBuilder));
    assertType('Illuminate\Types\Builder\CommentBuilder', $comment->newModelQuery());
    assertType('Illuminate\Types\Builder\CommentBuilder', $comment->newQueryWithoutRelationships());
    assertType('Illuminate\Types\Builder\CommentBuilder', $comment->newQueryWithoutScopes());
    assertType('Illuminate\Types\Builder\CommentBuilder', $comment->newQueryWithoutScope('foo'));
    assertType('Illuminate\Types\Builder\CommentBuilder', $comment->newQueryForRestoration(1));
    assertType('Illuminate\Types\Builder\CommentBuilder', $comment->newQuery()->where('foo', 'bar'));
    assertType('Illuminate\Types\Builder\CommentBuilder', $comment->newQuery()->foo());
    assertType('Illuminate\Types\Builder\Comment', $comment->newQuery()->create(['name' => 'John']));
}

class User extends Model
{
    /** @return HasMany<Post, $this> */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}

class Post extends Model
{
    /** @use HasBuilder<CommonBuilder<static>> */
    use HasBuilder;

    protected static string $builder = CommonBuilder::class;

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return MorphTo<\Illuminate\Database\Eloquent\Model, $this> */
    public function taggable(): MorphTo
    {
        return $this->morphTo();
    }
}

class ChildPost extends Post
{
}

class Comment extends Model
{
    /** @use HasBuilder<CommentBuilder> */
    use HasBuilder;

    protected static string $builder = CommentBuilder::class;
}

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @extends \Illuminate\Database\Eloquent\Builder<TModel>
 */
class CommonBuilder extends Builder
{
    /** @return $this */
    public function foo(): static
    {
        return $this->where('foo', 'bar');
    }
}

/** @extends CommonBuilder<Comment> */
class CommentBuilder extends CommonBuilder
{
}
