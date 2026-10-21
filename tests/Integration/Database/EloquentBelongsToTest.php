<?php

namespace Illuminate\Tests\Integration\Database\EloquentBelongsToTest;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class EloquentBelongsToTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('slug')->nullable();
            $table->unsignedInteger('parent_id')->nullable();
            $table->string('parent_slug')->nullable();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->nullable();
            $table->boolean('published')->default(false);
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->nullable();
            $table->unsignedInteger('reviewer_id')->nullable();
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->nullableMorphs('commentable');
        });

        $user = User::create(['slug' => Str::random()]);
        User::create(['parent_id' => $user->id, 'parent_slug' => $user->slug]);
    }

    public function testHasSelf()
    {
        $users = User::has('parent')->get();

        $this->assertCount(1, $users);
    }

    public function testHasSelfCustomOwnerKey()
    {
        $users = User::has('parentBySlug')->get();

        $this->assertCount(1, $users);
    }

    public function testAssociateWithModel()
    {
        $parent = User::doesntHave('parent')->first();
        $child = User::has('parent')->first();

        $parent->parent()->associate($child);

        $this->assertEquals($child->id, $parent->parent_id);
        $this->assertEquals($child->id, $parent->parent->id);
    }

    public function testAssociateWithId()
    {
        $parent = User::doesntHave('parent')->first();
        $child = User::has('parent')->first();

        $parent->parent()->associate($child->id);

        $this->assertEquals($child->id, $parent->parent_id);
        $this->assertEquals($child->id, $parent->parent->id);
    }

    public function testAssociateWithIdUnsetsLoadedRelation()
    {
        $child = User::has('parent')->with('parent')->first();

        // Overwrite the (loaded) parent relation
        $child->parent()->associate($child->id);

        $this->assertEquals($child->id, $child->parent_id);
        $this->assertFalse($child->relationLoaded('parent'));
    }

    public function testParentIsNotNull()
    {
        $child = User::has('parent')->first();
        $parent = null;

        $this->assertFalse($child->parent()->is($parent));
        $this->assertTrue($child->parent()->isNot($parent));
    }

    public function testParentIsModel()
    {
        $child = User::has('parent')->first();
        $parent = User::doesntHave('parent')->first();

        $this->assertTrue($child->parent()->is($parent));
        $this->assertFalse($child->parent()->isNot($parent));
    }

    public function testParentIsNotAnotherModel()
    {
        $child = User::has('parent')->first();
        $parent = new User;
        $parent->id = 3;

        $this->assertFalse($child->parent()->is($parent));
        $this->assertTrue($child->parent()->isNot($parent));
    }

    public function testNullParentIsNotModel()
    {
        $child = User::has('parent')->first();
        $child->parent()->dissociate();
        $parent = User::doesntHave('parent')->first();

        $this->assertFalse($child->parent()->is($parent));
        $this->assertTrue($child->parent()->isNot($parent));
    }

    public function testParentIsNotModelWithAnotherTable()
    {
        $child = User::has('parent')->first();
        $parent = User::doesntHave('parent')->first();
        $parent->setTable('foo');

        $this->assertFalse($child->parent()->is($parent));
        $this->assertTrue($child->parent()->isNot($parent));
    }

    public function testParentIsNotModelWithAnotherConnection()
    {
        $child = User::has('parent')->first();
        $parent = User::doesntHave('parent')->first();
        $parent->setConnection('foo');

        $this->assertFalse($child->parent()->is($parent));
        $this->assertTrue($child->parent()->isNot($parent));
    }

    public function testChaperoneGuessesTheInverseRelation()
    {
        $this->assertSame('posts', (new Post)->user()->chaperone()->getInverseRelationship());
    }

    public function testChaperoneThrowsWhenNoInverseRelationCanBeGuessed()
    {
        $relation = (new User)->parent();

        $this->expectException(RelationNotFoundException::class);

        $relation->chaperone();
    }

    public function testChaperoneThrowsForUnknownInverseRelation()
    {
        $this->expectException(RelationNotFoundException::class);

        (new Post)->user()->chaperone('nope');
    }

    public function testAssociateAddsChildrenToLoadedInverseRelation()
    {
        $user = User::create();
        $user->setRelation('posts', new Collection);

        $first = new Post;
        $first->user()->chaperone()->associate($user);

        $second = new Post;
        $second->user()->chaperone()->associate($user);

        $this->assertCount(2, $user->posts);
        $this->assertTrue($user->posts->contains($first));
        $this->assertTrue($user->posts->contains($second));
    }

    public function testAssociateDoesNotAddTheSameInstanceTwice()
    {
        $user = User::create();
        $user->setRelation('posts', new Collection);

        $post = new Post;
        $post->user()->chaperone()->associate($user);
        $post->user()->chaperone()->associate($user);

        $this->assertCount(1, $user->posts);
    }

    public function testAssociateLeavesUnloadedInverseRelationUnloaded()
    {
        $user = User::create();

        $post = new Post;
        $post->user()->chaperone()->associate($user);

        $this->assertFalse($user->relationLoaded('posts'));
    }

    public function testAssociateWithIdLeavesTheInverseRelationAlone()
    {
        $user = User::create();
        $user->setRelation('posts', new Collection);

        $post = new Post;
        $post->user()->chaperone()->associate($user->id);

        $this->assertCount(0, $user->posts);
    }

    public function testAssociateWithAnotherIdRemovesTheChildFromThePreviousOwner()
    {
        $previousOwner = User::create();
        $previousOwner->setRelation('posts', new Collection);

        $newOwner = User::create();

        $post = new Post;
        $post->user()->chaperone()->associate($previousOwner);
        $post->user()->chaperone()->associate($newOwner->id);

        $this->assertCount(0, $previousOwner->posts);
    }

    public function testAssociateRemovesTheChildFromAnUnsavedPreviousOwner()
    {
        $previousOwner = new User;
        $previousOwner->setRelation('posts', new Collection);

        $newOwner = new User;
        $newOwner->setRelation('posts', new Collection);

        $post = new Post;
        $post->user()->chaperone()->associate($previousOwner);
        $post->user()->chaperone()->associate($newOwner);

        $this->assertCount(0, $previousOwner->posts);
        $this->assertCount(1, $newOwner->posts);
    }

    public function testAssociateNullRemovesTheChildFromAnUnsavedPreviousOwner()
    {
        $previousOwner = new User;
        $previousOwner->setRelation('posts', new Collection);

        $post = new Post;
        $post->user()->chaperone()->associate($previousOwner);
        $post->user()->chaperone()->associate(null);

        $this->assertCount(0, $previousOwner->posts);
    }

    public function testDissociateRemovesTheChildFromLoadedInverseRelation()
    {
        $user = User::create();

        $post = new Post;
        $post->user()->chaperone()->associate($user);
        $user->setRelation('posts', new Collection([$post]));

        $post->user()->chaperone()->dissociate();

        $this->assertCount(0, $user->posts);
    }

    public function testAssociateAndDissociateReplaceLoadedSingularInverseRelation()
    {
        $user = User::create();
        $user->setRelation('post', new Post);

        $post = new Post;
        $post->user()->chaperone('post')->associate($user);
        $this->assertSame($post, $user->getRelation('post'));

        $post->user()->chaperone('post')->dissociate();
        $this->assertNull($user->getRelation('post'));
    }

    public function testAssociateUnloadsALoadedOneOfManyInverseRelation()
    {
        $user = User::create();
        $user->setRelation('latestPost', new Post);

        $post = new Post;
        $post->userByLatestPost()->associate($user);

        $this->assertFalse($user->relationLoaded('latestPost'));
    }

    public function testRechaperoningTheRelationReclassifiesTheInverseRelation()
    {
        $user = User::create();
        $user->setRelation('post', new Post);
        $user->setRelation('latestPost', new Post);

        $post = new Post;
        $relation = $post->user();
        $relation->chaperone('post')->associate($user);
        $relation->chaperone('latestPost')->associate($user);

        $this->assertFalse($user->relationLoaded('latestPost'));
    }

    public function testDissociateUnloadsALoadedOneOfManyInverseRelation()
    {
        $user = User::create();
        $post = Post::create(['user_id' => $user->id]);
        $post->setRelation('userByLatestPost', $user);
        $user->setRelation('latestPost', $post);

        $post->userByLatestPost()->dissociate();

        $this->assertFalse($user->relationLoaded('latestPost'));
    }

    public function testWithoutChaperoneStopsSyncingTheInverseRelation()
    {
        $user = User::create();
        $user->setRelation('posts', new Collection);

        $post = new Post;
        $post->user()->chaperone()->withoutChaperone()->associate($user);

        $this->assertCount(0, $user->posts);
    }

    public function testChaperoneDeclaredOnTheRelationSyncsTheInverseRelation()
    {
        $user = User::create();
        $user->setRelation('posts', new Collection);

        $post = new Post;
        $post->chaperonedUser()->associate($user);

        $this->assertCount(1, $user->posts);
    }

    public function testEagerLoadingDoesNotPopulateAToManyInverseRelation()
    {
        $user = User::create();
        Post::create(['user_id' => $user->id]);

        $post = Post::with('chaperonedUser')->sole();

        $this->assertFalse($post->chaperonedUser->relationLoaded('posts'));
    }

    public function testMorphToAssociateAndDissociateSyncTheLoadedInverseRelation()
    {
        $post = Post::create();
        $post->setRelation('comments', new Collection);

        $comment = new Comment;
        $comment->commentable()->associate($post);
        $this->assertCount(1, $post->comments);

        $comment->commentable()->dissociate();
        $this->assertCount(0, $post->comments);
    }

    public function testMorphToAssociateRemovesTheChildFromThePreviousOwner()
    {
        $previousOwner = Post::create();
        $previousOwner->setRelation('comments', new Collection);

        $newOwner = Post::create();
        $newOwner->setRelation('comments', new Collection);

        $comment = new Comment;
        $comment->commentable()->associate($previousOwner);
        $comment->commentable()->associate($newOwner);

        $this->assertCount(0, $previousOwner->comments);
        $this->assertCount(1, $newOwner->comments);
    }

    public function testMorphToChaperoneRequiresAnExplicitRelation()
    {
        $this->expectException(RelationNotFoundException::class);

        (new Comment)->commentable()->chaperone();
    }

    public function testMorphToAssociateThrowsForUnknownInverseRelation()
    {
        $comment = new Comment;
        $post = Post::create();

        $this->expectException(RelationNotFoundException::class);

        $comment->commentable()->chaperone('nope')->associate($post);
    }

    public function testAssociateDoesNotAddTheSameRowTwice()
    {
        $user = User::create();
        $post = Post::create(['user_id' => $user->id]);
        $user->setRelation('posts', new Collection([$post]));

        $refetched = Post::findOrFail($post->id);
        $refetched->user()->chaperone()->associate($user);

        $this->assertCount(1, $user->posts);
    }

    public function testTheLoadedInverseRelationIsReplacedRatherThanMutated()
    {
        $user = User::create();
        $user->setRelation('posts', new Collection);

        $posts = $user->posts;

        $post = new Post;
        $post->user()->chaperone()->associate($user);

        $this->assertCount(1, $user->posts);
        $this->assertCount(0, $posts);
    }

    public function testChaperoneFallsBackToTheSingularInverseRelation()
    {
        $this->assertSame('setting', (new Setting)->user()->chaperone()->getInverseRelationship());
    }

    public function testAssociateWithTheCurrentIdKeepsTheChildOnItsOwner()
    {
        $user = User::create();
        $user->setRelation('posts', new Collection);

        $post = new Post;
        $post->user()->chaperone()->associate($user);
        $post->user()->chaperone()->associate($user->id);

        $this->assertSame($user->id, $post->user_id);
        $this->assertCount(1, $user->posts);
    }

    public function testReassociatingTheSameOwnerPreservesCollectionOrder()
    {
        $user = User::create();
        $first = new Post;
        $second = new Post;
        $user->setRelation('posts', new Collection([$first, $second]));
        $first->setRelation('user', $user);

        $first->user()->chaperone()->associate($user);

        $this->assertSame([$first, $second], $user->posts->all());
    }

    public function testDissociateRemovesTheSameRowFromTheInverseCollection()
    {
        $user = User::create();
        $post = Post::create(['user_id' => $user->id]);

        $owner = User::with('posts')->findOrFail($user->id);
        $child = Post::findOrFail($post->id);
        $child->setRelation('user', $owner);

        $child->user()->chaperone()->dissociate();

        $this->assertCount(0, $owner->posts);
    }

    public function testDissociateClearsASingularInverseRelationHoldingTheSameRow()
    {
        $user = User::create();
        $post = Post::create(['user_id' => $user->id]);

        $owner = User::with('post')->findOrFail($user->id);
        $child = Post::findOrFail($post->id);
        $child->setRelation('user', $owner);

        $child->user()->chaperone('post')->dissociate();

        $this->assertNull($owner->getRelation('post'));
    }

    public function testDissociateLeavesAnUnrelatedSingularInverseRelationAlone()
    {
        $user = User::create();
        $other = Post::create(['user_id' => $user->id]);
        $user->setRelation('post', $other);

        $post = Post::create(['user_id' => $user->id]);
        $post->setRelation('user', $user);

        $post->user()->chaperone('post')->dissociate();

        $this->assertSame($other, $user->getRelation('post'));
    }

    public function testMorphToAssociateDoesNotMutateBeforeThrowing()
    {
        $first = Post::create();
        $second = Post::create();

        $comment = new Comment;
        $comment->commentable()->associate($first);

        try {
            $comment->commentable()->chaperone('nope')->associate($second);
            $this->fail('expected a RelationNotFoundException');
        } catch (RelationNotFoundException) {
        }

        $this->assertSame($first->id, $comment->commentable_id);
        $this->assertSame(Post::class, $comment->commentable_type);
    }

    public function testLazyLoadingPopulatesAToOneInverseRelation()
    {
        $user = User::create();
        $setting = Setting::create(['user_id' => $user->id]);

        $this->assertSame($setting, $setting->user->setting);
    }

    public function testEagerLoadingPopulatesAToOneInverseRelation()
    {
        $user = User::create();
        Setting::create(['user_id' => $user->id]);

        $setting = Setting::with('user')->sole();

        $this->assertSame($setting, $setting->user->setting);
    }

    public function testEagerLoadingKeepsAnExplicitlyLoadedInverseRelation()
    {
        $user = User::create();
        Setting::create(['user_id' => $user->id]);

        $setting = Setting::with('user.setting.userWithDefault')->sole();

        $this->assertTrue($setting->user->setting->relationLoaded('userWithDefault'));
    }

    public function testEagerLoadingKeepsAConstrainedInverseRelation()
    {
        $user = User::create();
        Setting::create(['user_id' => $user->id]);

        $setting = Setting::with(['user.setting' => fn ($query) => $query->whereRaw('1 = 0')])->sole();

        $this->assertNull($setting->user->setting);
    }

    public function testLazyLoadingFromAnUnsavedChildKeepsTheStoredInverseRelation()
    {
        $user = User::create();
        $stored = Setting::create(['user_id' => $user->id]);

        $draft = new Setting(['user_id' => $user->id]);

        $this->assertTrue($draft->user->setting->is($stored));
    }

    public function testAOneOfManyInverseRelationIsLeftUnloaded()
    {
        $user = User::create();
        $post = Post::create(['user_id' => $user->id]);

        $this->assertFalse($post->userByLatestPost->relationLoaded('latestPost'));
    }

    public function testAConstrainedInverseRelationIsLeftUnloaded()
    {
        $user = User::create();
        $post = Post::create(['user_id' => $user->id]);

        $this->assertFalse($post->userByPublishedPost->relationLoaded('publishedPost'));
    }

    public function testAnInverseRelationOnAnotherForeignKeyIsLeftUnloaded()
    {
        $reviewer = User::create();
        $setting = Setting::create(['reviewer_id' => $reviewer->id]);

        $this->assertFalse($setting->reviewer->relationLoaded('setting'));
    }

    public function testAssociateUnloadsALoadedInverseRelationOnAnotherForeignKey()
    {
        $reviewer = User::create();
        $reviewer->setRelation('setting', Setting::create(['user_id' => $reviewer->id]));

        (new Setting)->reviewer()->associate($reviewer);

        $this->assertFalse($reviewer->relationLoaded('setting'));
    }

    public function testADefaultOwnerIsNotChaperoned()
    {
        $setting = new Setting;

        $this->assertFalse($setting->userWithDefault->relationLoaded('setting'));
    }

    public function testWithoutChaperoneStopsPopulatingTheInverseRelation()
    {
        $user = User::create();
        $setting = Setting::create(['user_id' => $user->id]);

        $owner = $setting->user()->withoutChaperone()->getResults();

        $this->assertFalse($owner->relationLoaded('setting'));
    }

    public function testMorphToLazyLoadingPopulatesAToOneInverseRelation()
    {
        $post = Post::create();
        $comment = Comment::create(['commentable_type' => Post::class, 'commentable_id' => $post->id]);

        $this->assertSame($comment, $comment->chaperonedCommentable->comment);
    }

    public function testMorphToEagerLoadingPopulatesAToOneInverseRelation()
    {
        $post = Post::create();
        Comment::create(['commentable_type' => Post::class, 'commentable_id' => $post->id]);

        $comment = Comment::with('chaperonedCommentable')->sole();

        $this->assertSame($comment, $comment->chaperonedCommentable->comment);
    }

    public function testMorphToEagerLoadingClassifiesTheInverseRelationPerOwnerClass()
    {
        $post = Post::create();
        Comment::create(['commentable_type' => Post::class, 'commentable_id' => $post->id]);
        $user = User::create();
        Comment::create(['commentable_type' => User::class, 'commentable_id' => $user->id]);

        [$postComment, $userComment] = Comment::with('chaperonedCommentable')->orderBy('id')->get();

        $this->assertSame($postComment, $postComment->chaperonedCommentable->comment);
        $this->assertFalse($userComment->chaperonedCommentable->relationLoaded('comment'));
    }

    public function testMorphToLoadingThrowsForUnknownInverseRelation()
    {
        $setting = Setting::create();
        $comment = Comment::create(['commentable_type' => Setting::class, 'commentable_id' => $setting->id]);

        $this->expectException(RelationNotFoundException::class);

        $comment->chaperonedCommentable;
    }
}

class User extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function parentBySlug()
    {
        return $this->belongsTo(self::class, 'parent_slug', 'slug');
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function post()
    {
        return $this->hasOne(Post::class);
    }

    public function setting()
    {
        return $this->hasOne(Setting::class);
    }

    public function latestPost()
    {
        return $this->hasOne(Post::class)->latestOfMany();
    }

    public function publishedPost()
    {
        return $this->hasOne(Post::class)->where('published', true);
    }

    public function comment()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}

class Setting extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class)->chaperone();
    }

    public function userWithDefault()
    {
        return $this->belongsTo(User::class, 'user_id')->withDefault()->chaperone('setting');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id')->chaperone();
    }
}

class Post extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function chaperonedUser()
    {
        return $this->belongsTo(User::class, 'user_id')->chaperone('posts');
    }

    public function userByLatestPost()
    {
        return $this->belongsTo(User::class, 'user_id')->chaperone('latestPost');
    }

    public function userByPublishedPost()
    {
        return $this->belongsTo(User::class, 'user_id')->chaperone('publishedPost');
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function comment()
    {
        return $this->morphOne(Comment::class, 'commentable');
    }
}

class Comment extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    public function commentable()
    {
        return $this->morphTo()->chaperone('comments');
    }

    public function chaperonedCommentable()
    {
        return $this->morphTo('chaperonedCommentable', 'commentable_type', 'commentable_id')->chaperone('comment');
    }
}
