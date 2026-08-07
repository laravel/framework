<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Events\Dispatcher;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentSoftDeletingPivotTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $db = new DB;

        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $db->setEventDispatcher(new Dispatcher);
        $db->bootEloquent();
        $db->setAsGlobal();

        $this->createSchema();
    }

    /**
     * Setup the database schema.
     *
     * @return void
     */
    protected function createSchema()
    {
        $this->schema()->create('posts', function ($table) {
            $table->increments('id');
            $table->string('title');
            $table->timestamps();
        });

        $this->schema()->create('tags', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        $this->schema()->create('post_tag', function ($table) {
            $table->increments('id');
            $table->integer('post_id');
            $table->integer('tag_id');
            $table->timestamps();
            $table->softDeletes();
        });

        $this->schema()->create('taggables', function ($table) {
            $table->increments('id');
            $table->integer('tag_id');
            $table->integer('taggable_id');
            $table->string('taggable_type');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->schema()->drop('posts');
        $this->schema()->drop('tags');
        $this->schema()->drop('post_tag');
        $this->schema()->drop('taggables');

        Eloquent::unsetEventDispatcher();

        parent::tearDown();
    }

    /**
     * Tests...
     */
    public function testDeleteSoftDeletesTheUnderlyingRow()
    {
        [$post] = $this->createPostWithTag();

        $post->tags->first()->pivot->delete();

        $this->assertNotNull($this->pivotRow()->deleted_at);
    }

    public function testDeleteSynchronizesTheStateOfTheModelInstance()
    {
        [$post] = $this->createPostWithTag();

        $pivot = $post->tags->first()->pivot;
        $pivot->delete();

        $this->assertNotNull($pivot->deleted_at);
        $this->assertTrue($pivot->trashed());
        $this->assertTrue($pivot->exists);
        $this->assertNotNull($pivot->updated_at);
    }

    public function testDeleteReturnsTheNumberOfAffectedRows()
    {
        [$post] = $this->createPostWithTag();

        $this->assertSame(1, $post->tags->first()->pivot->delete());
    }

    public function testDeleteFiresTheSoftDeletedEvent()
    {
        [$post] = $this->createPostWithTag();

        $fired = false;

        SoftDeletingPivotTestPostTag::softDeleted(function () use (&$fired) {
            $fired = true;
        });

        $post->tags->first()->pivot->delete();

        $this->assertTrue($fired);
    }

    public function testForceDeleteRemovesTheUnderlyingRow()
    {
        [$post] = $this->createPostWithTag();

        $post->tags->first()->pivot->forceDelete();

        $this->assertNull($this->pivotRow());
    }

    public function testRestoreClearsTheDeletedAtColumn()
    {
        [$post] = $this->createPostWithTag();

        $pivot = $post->tags->first()->pivot;
        $pivot->delete();
        $pivot->restore();

        $this->assertFalse($pivot->trashed());
        $this->assertNull($this->pivotRow()->deleted_at);
    }

    public function testDetachSoftDeletesThePivotRow()
    {
        [$post, $tag] = $this->createPostWithTag();

        $post->tags()->detach($tag->id);

        $this->assertNotNull($this->pivotRow()->deleted_at);
    }

    public function testPivotsWithoutSoftDeletesStillHardDelete()
    {
        [$post] = $this->createPostWithTag();

        $rowsAffected = $post->plainTags->first()->pivot->delete();

        $this->assertSame(1, $rowsAffected);
        $this->assertNull($this->pivotRow());
    }

    public function testMorphPivotDeleteSoftDeletesTheUnderlyingRow()
    {
        [$post] = $this->createPostWithMorphedTag();

        $pivot = $post->morphedTags->first()->pivot;
        $pivot->delete();

        $this->assertNotNull($this->connection()->table('taggables')->first()->deleted_at);
        $this->assertTrue($pivot->trashed());
    }

    public function testMorphPivotForceDeleteRemovesTheUnderlyingRow()
    {
        [$post] = $this->createPostWithMorphedTag();

        $post->morphedTags->first()->pivot->forceDelete();

        $this->assertSame(0, $this->connection()->table('taggables')->count());
    }

    public function testMorphPivotDeleteIsScopedToTheMorphType()
    {
        [$post] = $this->createPostWithMorphedTag();

        $this->connection()->table('taggables')->insert([
            'tag_id' => 1,
            'taggable_id' => 1,
            'taggable_type' => SoftDeletingPivotTestTag::class,
        ]);

        $post->morphedTags->first()->pivot->delete();

        $this->assertSame(1, $this->connection()->table('taggables')
            ->whereNull('deleted_at')
            ->where('taggable_type', SoftDeletingPivotTestTag::class)
            ->count());
    }

    /**
     * Helpers...
     */
    protected function createPostWithTag()
    {
        $post = SoftDeletingPivotTestPost::create(['title' => 'post']);
        $tag = SoftDeletingPivotTestTag::create(['name' => 'tag']);

        $post->tags()->attach($tag);

        return [$post->load('tags', 'plainTags'), $tag];
    }

    protected function createPostWithMorphedTag()
    {
        $post = SoftDeletingPivotTestPost::create(['title' => 'post']);
        $tag = SoftDeletingPivotTestTag::create(['name' => 'tag']);

        $post->morphedTags()->attach($tag);

        return [$post->load('morphedTags'), $tag];
    }

    protected function pivotRow()
    {
        return $this->connection()->table('post_tag')->first();
    }

    /**
     * Get a database connection instance.
     *
     * @return \Illuminate\Database\Connection
     */
    protected function connection()
    {
        return Eloquent::getConnectionResolver()->connection();
    }

    /**
     * Get a schema builder instance.
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    protected function schema()
    {
        return $this->connection()->getSchemaBuilder();
    }
}

/**
 * Eloquent Models...
 */
class SoftDeletingPivotTestPost extends Eloquent
{
    protected $table = 'posts';
    protected $guarded = [];

    public function tags()
    {
        return $this->belongsToMany(SoftDeletingPivotTestTag::class, 'post_tag', 'post_id', 'tag_id')
            ->using(SoftDeletingPivotTestPostTag::class)
            ->withTimestamps();
    }

    public function plainTags()
    {
        return $this->belongsToMany(SoftDeletingPivotTestTag::class, 'post_tag', 'post_id', 'tag_id')
            ->using(SoftDeletingPivotTestPlainPostTag::class)
            ->withTimestamps();
    }

    public function morphedTags()
    {
        return $this->morphToMany(SoftDeletingPivotTestTag::class, 'taggable', 'taggables', 'taggable_id', 'tag_id')
            ->using(SoftDeletingPivotTestTaggable::class)
            ->withTimestamps();
    }
}

/**
 * Eloquent Models...
 */
class SoftDeletingPivotTestTag extends Eloquent
{
    protected $table = 'tags';
    protected $guarded = [];
}

/**
 * Eloquent Models...
 */
class SoftDeletingPivotTestPostTag extends Pivot
{
    use SoftDeletes;

    protected $table = 'post_tag';
}

/**
 * Eloquent Models...
 */
class SoftDeletingPivotTestPlainPostTag extends Pivot
{
    protected $table = 'post_tag';
}

/**
 * Eloquent Models...
 */
class SoftDeletingPivotTestTaggable extends MorphPivot
{
    use SoftDeletes;

    protected $table = 'taggables';
}
