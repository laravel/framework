<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Query\Expression;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class DatabaseEloquentCrossDatabaseRelationshipTest extends TestCase
{
    protected function setUp(): void
    {
        $db = new DB;

        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ], 'primary');

        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ], 'reporting');

        $db->bootEloquent();
        $db->setAsGlobal();

        $this->createSchema();
    }

    protected function tearDown(): void
    {
        $this->schema('primary')->drop('cross_db_posts');
        $this->schema('reporting')->drop('cross_db_comments');
        $this->schema('reporting')->drop('cross_db_likes');

        Eloquent::unsetConnectionResolver();
    }

    /**
     * Tier 1: same connection — generated SQL must remain a correlated EXISTS
     * subquery, with no whereIn rewriting. Guards against regressions on the
     * default code path.
     */
    public function testSameConnectionStillUsesExistsSubquery()
    {
        $sql = strtolower(SameConnectionPost::whereHas('localComments')->toSql());

        $this->assertStringContainsString('exists', $sql);
        $this->assertStringNotContainsString(' in (', $sql);
    }

    public function testHasReturnsPostsWithAtLeastOneRelatedRow()
    {
        $this->seedData();

        $ids = CrossDbPost::has('comments')->orderBy('id')->pluck('id')->all();

        $this->assertEquals([1, 2, 3], $ids);
    }

    public function testWhereHasAppliesCallbackFilter()
    {
        $this->seedData();

        $ids = CrossDbPost::whereHas('comments', function ($query) {
            $query->where('content', 'like', 'code%');
        })->orderBy('id')->pluck('id')->all();

        $this->assertEquals([1, 2], $ids);
    }

    public function testWhereHasWithGreaterThanCountConstraint()
    {
        $this->seedData();

        $ids = CrossDbPost::whereHas('comments', null, '>=', 2)
            ->orderBy('id')->pluck('id')->all();

        $this->assertEquals([1], $ids);
    }

    public function testWhereHasWithLessThanOrEqualCountConstraintIncludesZeroCountParents()
    {
        $this->seedData();

        // <= 1 should match posts with 0 or 1 comments. Posts 2, 3 each have 1.
        // Post 4 has 0. Post 1 has 3 (excluded). This exercises the
        // complement-flip logic for operators where a zero count satisfies
        // the constraint.
        $ids = CrossDbPost::whereHas('comments', null, '<=', 1)
            ->orderBy('id')->pluck('id')->all();

        $this->assertEquals([2, 3, 4], $ids);
    }

    public function testWhereHasWithEqualsZeroIsEquivalentToDoesntHave()
    {
        $this->seedData();

        $ids = CrossDbPost::whereHas('comments', null, '=', 0)
            ->orderBy('id')->pluck('id')->all();

        $this->assertEquals([4], $ids);
    }

    public function testDoesntHaveReturnsParentsWithNoRelatedRows()
    {
        $this->seedData();

        $ids = CrossDbPost::doesntHave('comments')->orderBy('id')->pluck('id')->all();

        $this->assertEquals([4], $ids);
    }

    public function testWhereDoesntHaveWithFilter()
    {
        $this->seedData();

        // Post 3's only comment is "other"; post 4 has no comments. Both qualify.
        $ids = CrossDbPost::whereDoesntHave('comments', function ($query) {
            $query->where('content', 'like', 'code%');
        })->orderBy('id')->pluck('id')->all();

        $this->assertEquals([3, 4], $ids);
    }

    public function testWhereDoesntHaveWithFilterThatMatchesNothingReturnsAllParents()
    {
        $this->seedData();

        $ids = CrossDbPost::whereDoesntHave('comments', function ($query) {
            $query->where('content', 'this-string-matches-nothing');
        })->orderBy('id')->pluck('id')->all();

        $this->assertEquals([1, 2, 3, 4], $ids);
    }

    public function testWhereHasWithFilterThatMatchesNothingReturnsNoParents()
    {
        $this->seedData();

        $rows = CrossDbPost::whereHas('comments', function ($query) {
            $query->where('content', 'this-string-matches-nothing');
        })->get();

        $this->assertCount(0, $rows);
    }

    public function testOrWhereHasCombinesWithExistingWhereClause()
    {
        $this->seedData();

        $ids = CrossDbPost::where('title', 'D')
            ->orWhereHas('comments', function ($query) {
                $query->where('content', 'like', 'code%');
            })
            ->orderBy('id')
            ->pluck('id')->all();

        // Post 4 matches by title; posts 1 and 2 match via the comment filter.
        $this->assertEquals([1, 2, 4], $ids);
    }

    public function testBelongsToCrossConnection()
    {
        $this->seedData();

        $ids = CrossDbComment::whereHas('post', function ($query) {
            $query->where('title', 'A');
        })->orderBy('id')->pluck('id')->all();

        $this->assertEquals([1, 2, 3], $ids);
    }

    public function testWithCountAttachesPerModelCount()
    {
        $this->seedData();

        $posts = CrossDbPost::withCount('comments')->orderBy('id')->get();

        $this->assertEquals(3, $posts->firstWhere('id', 1)->comments_count);
        $this->assertEquals(1, $posts->firstWhere('id', 2)->comments_count);
        $this->assertEquals(1, $posts->firstWhere('id', 3)->comments_count);
        $this->assertEquals(0, $posts->firstWhere('id', 4)->comments_count);
    }

    public function testWithCountRespectsConstraintClosureAndAlias()
    {
        $this->seedData();

        $posts = CrossDbPost::withCount([
            'comments as code_count' => function ($query) {
                $query->where('content', 'like', 'code%');
            },
        ])->orderBy('id')->get();

        $this->assertEquals(2, $posts->firstWhere('id', 1)->code_count);
        $this->assertEquals(1, $posts->firstWhere('id', 2)->code_count);
        $this->assertEquals(0, $posts->firstWhere('id', 3)->code_count);
        $this->assertEquals(0, $posts->firstWhere('id', 4)->code_count);
    }

    public function testWithCountOnFirstResultStampsCount()
    {
        $this->seedData();

        $post = CrossDbPost::withCount('comments')->where('id', 1)->first();

        $this->assertEquals(3, $post->comments_count);
    }

    public function testWithExistsAttachesBooleanFlag()
    {
        $this->seedData();

        $posts = CrossDbPost::withExists('comments')->orderBy('id')->get();

        $this->assertTrue($posts->firstWhere('id', 1)->comments_exists);
        $this->assertTrue($posts->firstWhere('id', 2)->comments_exists);
        $this->assertTrue($posts->firstWhere('id', 3)->comments_exists);
        $this->assertFalse($posts->firstWhere('id', 4)->comments_exists);
    }

    public function testWithMaxOnRelatedNumericColumn()
    {
        $this->seedData();

        $posts = CrossDbPost::withMax('comments', 'id')->orderBy('id')->get();

        $this->assertEquals(3, $posts->firstWhere('id', 1)->comments_max_id);
        $this->assertEquals(4, $posts->firstWhere('id', 2)->comments_max_id);
        $this->assertEquals(5, $posts->firstWhere('id', 3)->comments_max_id);
        $this->assertNull($posts->firstWhere('id', 4)->comments_max_id);
    }

    public function testWithWhereHasFiltersAndEagerLoadsRelation()
    {
        $this->seedData();

        $posts = CrossDbPost::withWhereHas('comments', function ($query) {
            $query->where('content', 'like', 'code%');
        })->orderBy('id')->get();

        $this->assertEquals([1, 2], $posts->pluck('id')->all());
        $this->assertCount(2, $posts->firstWhere('id', 1)->comments);
        $this->assertCount(1, $posts->firstWhere('id', 2)->comments);
    }

    public function testMorphManyRelationsAreResolvedAcrossConnections()
    {
        $this->seedData();
        $this->seedLikes();

        $ids = CrossDbPost::has('likes')->orderBy('id')->pluck('id')->all();

        $this->assertEquals([1, 3], $ids);
    }

    public function testUnsupportedRelationTypeRaisesDescriptiveException()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cross-connection relationship existence queries are not supported');

        // BelongsToMany is intentionally out of scope for the first iteration.
        CrossDbPost::has('taggedComments')->get();
    }

    public function testExpressionCountIsRejectedOnCrossConnectionPath()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Expression count values');

        CrossDbPost::whereHas('comments', null, '>=', new Expression('2'))->get();
    }

    /**
     * Schema setup.
     */
    protected function createSchema(): void
    {
        $this->schema('primary')->create('cross_db_posts', function ($table) {
            $table->increments('id');
            $table->string('title');
        });

        $this->schema('reporting')->create('cross_db_comments', function ($table) {
            $table->increments('id');
            $table->integer('post_id');
            $table->string('content');
        });

        $this->schema('reporting')->create('cross_db_likes', function ($table) {
            $table->increments('id');
            $table->integer('likeable_id');
            $table->string('likeable_type');
        });
    }

    /**
     * Seed canonical post/comment fixtures.
     *
     * Post 1: 3 comments (2 with "code", 1 "other").
     * Post 2: 1 comment (with "code").
     * Post 3: 1 comment ("other").
     * Post 4: 0 comments.
     */
    protected function seedData(): void
    {
        CrossDbPost::create(['id' => 1, 'title' => 'A']);
        CrossDbPost::create(['id' => 2, 'title' => 'B']);
        CrossDbPost::create(['id' => 3, 'title' => 'C']);
        CrossDbPost::create(['id' => 4, 'title' => 'D']);

        CrossDbComment::create(['id' => 1, 'post_id' => 1, 'content' => 'code one']);
        CrossDbComment::create(['id' => 2, 'post_id' => 1, 'content' => 'code two']);
        CrossDbComment::create(['id' => 3, 'post_id' => 1, 'content' => 'other comment']);
        CrossDbComment::create(['id' => 4, 'post_id' => 2, 'content' => 'code three']);
        CrossDbComment::create(['id' => 5, 'post_id' => 3, 'content' => 'other comment']);
    }

    /**
     * Seed morph-many likes on a subset of posts.
     */
    protected function seedLikes(): void
    {
        CrossDbLike::create(['likeable_id' => 1, 'likeable_type' => CrossDbPost::class]);
        CrossDbLike::create(['likeable_id' => 1, 'likeable_type' => CrossDbPost::class]);
        CrossDbLike::create(['likeable_id' => 3, 'likeable_type' => CrossDbPost::class]);
    }

    protected function schema($connection)
    {
        return DB::connection($connection)->getSchemaBuilder();
    }
}

class CrossDbPost extends Eloquent
{
    protected $connection = 'primary';
    protected $table = 'cross_db_posts';
    protected $guarded = [];
    public $timestamps = false;

    public function comments()
    {
        return $this->hasMany(CrossDbComment::class, 'post_id');
    }

    public function likes()
    {
        return $this->morphMany(CrossDbLike::class, 'likeable');
    }

    public function taggedComments()
    {
        // BelongsToMany — intentionally exercises the unsupported-relation
        // branch for cross-connection resolution.
        return $this->belongsToMany(CrossDbComment::class, 'post_tag_pivot');
    }
}

class CrossDbComment extends Eloquent
{
    protected $connection = 'reporting';
    protected $table = 'cross_db_comments';
    protected $guarded = [];
    public $timestamps = false;

    public function post()
    {
        return $this->belongsTo(CrossDbPost::class, 'post_id');
    }
}

class CrossDbLike extends Eloquent
{
    protected $connection = 'reporting';
    protected $table = 'cross_db_likes';
    protected $guarded = [];
    public $timestamps = false;
}

/**
 * Used by the tier 1 same-connection guard test. Both this model and its
 * related model live on the "primary" connection, so the existing EXISTS
 * subquery path must be preserved exactly.
 */
class SameConnectionPost extends Eloquent
{
    protected $connection = 'primary';
    protected $table = 'cross_db_posts';
    protected $guarded = [];
    public $timestamps = false;

    public function localComments()
    {
        return $this->hasMany(SameConnectionComment::class, 'post_id');
    }
}

class SameConnectionComment extends Eloquent
{
    protected $connection = 'primary';
    protected $table = 'same_connection_comments';
    protected $guarded = [];
    public $timestamps = false;
}
