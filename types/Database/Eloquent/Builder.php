<?php

namespace Illuminate\Types\Builder;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\HasBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Query\Builder as QueryBuilder;
use User;

use function PHPStan\Testing\assertType;

/** @param \Illuminate\Database\Eloquent\Builder<\User> $query */
function test(
    Builder $query,
    Post $post,
    ChildPost $childPost,
    Comment $comment,
    QueryBuilder $queryBuilder
): void {
    assertType('Illuminate\Database\Eloquent\Builder<User>', $query->where('id', 1));
    assertType('Illuminate\Database\Eloquent\Builder<User>', $query->orWhere('name', 'John'));
    assertType('Illuminate\Database\Eloquent\Builder<User>', $query->whereNot('status', 'active'));
    assertType('Illuminate\Database\Eloquent\Builder<User>', $query->with('relation'));
    assertType('Illuminate\Database\Eloquent\Builder<User>', $query->with(['relation' => ['foo' => fn ($q) => $q]]));
    assertType('Illuminate\Database\Eloquent\Builder<User>', $query->with(['relation' => function ($query) {
        // assertType('Illuminate\Database\Eloquent\Relations\Relation<*,*,*>', $query);
    }]));
    assertType('Illuminate\Database\Eloquent\Builder<User>', $query->without('relation'));
    assertType('Illuminate\Database\Eloquent\Builder<User>', $query->withOnly(['relation']));
    assertType('Illuminate\Database\Eloquent\Builder<User>', $query->withOnly(['relation' => ['foo' => fn ($q) => $q]]));
    assertType('Illuminate\Database\Eloquent\Builder<User>', $query->withOnly(['relation' => function ($query) {
        // assertType('Illuminate\Database\Eloquent\Relations\Relation<*,*,*>', $query);
    }]));
    assertType('array<int, User>', $query->getModels());
    assertType('array<int, User>', $query->eagerLoadRelations([]));
    assertType('Illuminate\Database\Eloquent\Collection<int, User>', $query->get());
    assertType('Illuminate\Database\Eloquent\Collection<int, User>', $query->hydrate([]));
    assertType('Illuminate\Database\Eloquent\Collection<int, User>', $query->fromQuery('foo', []));
    assertType('Illuminate\Database\Eloquent\Collection<int, User>', $query->findMany([1, 2, 3]));
    assertType('Illuminate\Database\Eloquent\Collection<int, User>', $query->findOrFail([1]));
    assertType('Illuminate\Database\Eloquent\Collection<int, User>', $query->findOrNew([1]));
    assertType('Illuminate\Database\Eloquent\Collection<int, User>', $query->find([1]));
    assertType('Illuminate\Database\Eloquent\Collection<int, User>', $query->findOr([1], callback: fn () => 42));
    assertType('User', $query->findOrFail(1));
    assertType('User|null', $query->find(1));
    assertType('int|User', $query->findOr(1, fn () => 42));
    assertType('int|User', $query->findOr(1, callback: fn () => 42));
    assertType('User|null', $query->first());
    assertType('int|User', $query->firstOr(fn () => 42));
    assertType('int|User', $query->firstOr(callback: fn () => 42));
    assertType('User', $query->firstOrNew(['id' => 1]));
    assertType('User', $query->findOrNew(1));
    assertType('User', $query->firstOrCreate(['id' => 1]));
    assertType('User', $query->create(['name' => 'John']));
    assertType('User', $query->forceCreate(['name' => 'John']));
    assertType('User', $query->forceCreateQuietly(['name' => 'John']));
    assertType('User', $query->getModel());
    assertType('User', $query->make(['name' => 'John']));
    assertType('User', $query->forceCreate(['name' => 'John']));
    assertType('User', $query->updateOrCreate(['id' => 1], ['name' => 'John']));
    assertType('User', $query->firstOrFail());
    assertType('User', $query->sole());
    assertType('Illuminate\Support\LazyCollection<int, User>', $query->cursor());
    assertType('Illuminate\Support\Collection<(int|string), mixed>', $query->pluck('foo'));
    assertType('Illuminate\Database\Eloquent\Relations\Relation<Illuminate\Database\Eloquent\Model, User, *>', $query->getRelation('foo'));
    assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Types\Builder\Post>', $query->setModel(new Post()));

    assertType('Illuminate\Database\Eloquent\Builder<User>', $query->has('foo'));
    assertType('Illuminate\Database\Eloquent\Builder<User>', $query->has($post->users()));
    assertType('Illuminate\Database\Eloquent\Builder<User>', $query->orHas($post->users()));
    assertType('Illuminate\Database\Eloquent\Builder<User>', $query->doesntHave($post->users()));
    assertType('Illuminate\Database\Eloquent\Builder<User>', $query->orDoesntHave($post->users()));
    assertType('Illuminate\Database\Eloquent\Builder<User>', $query->whereHas($post->users()));
    assertType('Illuminate\Database\Eloquent\Builder<User>', $query->withWhereHas($post->users()));
    assertType('Illuminate\Database\Eloquent\Builder<User>', $query->orWhereHas($post->users()));
    assertType('Illuminate\Database\Eloquent\Builder<User>', $query->whereDoesntHave($post->users()));
    assertType('Illuminate\Database\Eloquent\Builder<User>', $query->orWhereDoesntHave($post->users()));
    assertType('Illuminate\Database\Eloquent\Builder<User>', $query->hasMorph($post->taggable(), 'taggable'));
    assertType('Illuminate\Database\Eloquent\Builder<User>', $query->orHasMorph($post->taggable(), 'taggable'));
    assertType('Illuminate\Database\Eloquent\Builder<User>', $query->doesntHaveMorph($post->taggable(), 'taggable'));
    assertType('Illuminate\Database\Eloquent\Builder<User>', $query->orDoesntHaveMorph($post->taggable(), 'taggable'));
    assertType('Illuminate\Database\Eloquent\Builder<User>', $query->whereHasMorph($post->taggable(), 'taggable'));
    assertType('Illuminate\Database\Eloquent\Builder<User>', $query->whereDoesntHaveMorph($post->taggable(), 'taggable'));
    assertType('Illuminate\Database\Eloquent\Builder<User>', $query->orWhereDoesntHaveMorph($post->taggable(), 'taggable'));
    assertType('Illuminate\Database\Eloquent\Builder<User>', $query->whereRelation($post->users(), 'foo'));
    assertType('Illuminate\Database\Eloquent\Builder<User>', $query->orWhereRelation($post->users(), 'foo'));
    assertType('Illuminate\Database\Eloquent\Builder<User>', $query->whereMorphRelation($post->taggable(), 'taggable', 'foo'));
    assertType('Illuminate\Database\Eloquent\Builder<User>', $query->orWhereMorphRelation($post->taggable(), 'taggable', 'foo'));
    assertType('Illuminate\Database\Eloquent\Builder<User>', $query->whereMorphedTo($post->taggable(), new Post()));
    assertType('Illuminate\Database\Eloquent\Builder<User>', $query->whereNotMorphedTo($post->taggable(), new Post()));
    assertType('Illuminate\Database\Eloquent\Builder<User>', $query->orWhereMorphedTo($post->taggable(), new Post()));
    assertType('Illuminate\Database\Eloquent\Builder<User>', $query->orWhereNotMorphedTo($post->taggable(), new Post()));

    $query->chunk(1, function ($users, $page) {
        assertType('Illuminate\Support\Collection<int, User>', $users);
        assertType('int', $page);
    });
    $query->chunkById(1, function ($users, $page) {
        assertType('Illuminate\Support\Collection<int, User>', $users);
        assertType('int', $page);
    });
    $query->chunkMap(function ($users) {
        assertType('User', $users);
    });
    $query->chunkByIdDesc(1, function ($users, $page) {
        assertType('Illuminate\Support\Collection<int, User>', $users);
        assertType('int', $page);
    });
    $query->each(function ($users, $page) {
        assertType('User', $users);
        assertType('int', $page);
    });
    $query->eachById(function ($users, $page) {
        assertType('User', $users);
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

class Post extends Model
{
    /** @use HasBuilder<CommonBuilder<static>> */
    use HasBuilder;

    protected static string $builder = CommonBuilder::class;

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
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
