<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentBelongsToManyWithAttributesPendingTest extends TestCase
{
    protected function setUp(): void
    {
        $db = new DB;

        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $db->bootEloquent();
        $db->setAsGlobal();
        $this->createSchema();
    }

    public function testCreatesPendingAttributesAndPivotValues(): void
    {
        $post = ManyToManyPendingAttributesPost::create();
        $tag = $post->metaTags()->create(['name' => 'long article']);

        $this->assertSame('long article', $tag->name);
        $this->assertTrue($tag->visible);

        $pivot = DB::table('pending_attributes_pivot')->first();
        $this->assertSame('meta', $pivot->type);
        $this->assertSame($post->id, $pivot->post_id);
        $this->assertSame($tag->id, $pivot->tag_id);
    }

    public function testQueriesPendingAttributesAndPivotValues(): void
    {
        $post = new ManyToManyPendingAttributesPost(['id' => 2]);
        $wheres = $post->metaTags()->toBase()->wheres;

        $this->assertContains([
            'type' => 'Basic',
            'column' => 'pending_attributes_pivot.tag_id',
            'operator' => '=',
            'value' => 2,
            'boolean' => 'and',
        ], $wheres);

        $this->assertContains([
            'type' => 'Basic',
            'column' => 'pending_attributes_pivot.type',
            'operator' => '=',
            'value' => 'meta',
            'boolean' => 'and',
        ], $wheres);

        // Ensure no other wheres exist
        $this->assertCount(2, $wheres);
    }

    public function testMorphToManyPendingAttributes(): void
    {
        $post = new ManyToManyPendingAttributesPost(['id' => 2]);
        $wheres = $post->morphedTags()->toBase()->wheres;

        $this->assertContains([
            'type' => 'Basic',
            'column' => 'pending_attributes_taggables.type',
            'operator' => '=',
            'value' => 'meta',
            'boolean' => 'and',
        ], $wheres);

        $this->assertContains([
            'type' => 'Basic',
            'column' => 'pending_attributes_taggables.taggable_type',
            'operator' => '=',
            'value' => ManyToManyPendingAttributesPost::class,
            'boolean' => 'and',
        ], $wheres);

        $this->assertContains([
            'type' => 'Basic',
            'column' => 'pending_attributes_taggables.taggable_id',
            'operator' => '=',
            'value' => 2,
            'boolean' => 'and',
        ], $wheres);

        // Ensure no other wheres exist
        $this->assertCount(3, $wheres);

        $tag = $post->morphedTags()->create(['name' => 'new tag']);

        $this->assertTrue($tag->visible);
        $this->assertSame('new tag', $tag->name);
        $this->assertSame($tag->id, $post->morphedTags()->first()->id);
    }

    public function testMorphedByManyPendingAttributes(): void
    {
        $tag = new ManyToManyPendingAttributesTag(['id' => 4]);
        $wheres = $tag->morphedPosts()->toBase()->wheres;

        $this->assertContains([
            'type' => 'Basic',
            'column' => 'pending_attributes_taggables.type',
            'operator' => '=',
            'value' => 'meta',
            'boolean' => 'and',
        ], $wheres);

        $this->assertContains([
            'type' => 'Basic',
            'column' => 'pending_attributes_taggables.taggable_type',
            'operator' => '=',
            'value' => ManyToManyPendingAttributesPost::class,
            'boolean' => 'and',
        ], $wheres);

        $this->assertContains([
            'type' => 'Basic',
            'column' => 'pending_attributes_taggables.tag_id',
            'operator' => '=',
            'value' => 4,
            'boolean' => 'and',
        ], $wheres);

        // Ensure no other wheres exist
        $this->assertCount(3, $wheres);

        $post = $tag->morphedPosts()->create();
        $this->assertSame('Title!', $post->title);
        $this->assertSame($post->id, $tag->morphedPosts()->first()->id);
    }

    protected function createSchema()
    {
        $this->schema()->create('pending_attributes_posts', function ($table) {
            $table->increments('id');
            $table->string('title')->nullable();
            $table->timestamps();
        });

        $this->schema()->create('pending_attributes_tags', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->boolean('visible')->nullable();
            $table->timestamps();
        });

        $this->schema()->create('pending_attributes_pivot', function ($table) {
            $table->integer('post_id');
            $table->integer('tag_id');
            $table->string('type');
        });

        $this->schema()->create('pending_attributes_taggables', function ($table) {
            $table->integer('tag_id');
            $table->integer('taggable_id');
            $table->string('taggable_type');
            $table->string('type');
        });
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->schema()->drop('pending_attributes_posts');
        $this->schema()->drop('pending_attributes_tags');
        $this->schema()->drop('pending_attributes_pivot');
    }

    /**
     * Get a database connection instance.
     *
     * @return \Illuminate\Database\Connection
     */
    protected function connection($connection = 'default')
    {
        return Model::getConnectionResolver()->connection($connection);
    }

    /**
     * Get a schema builder instance.
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    protected function schema($connection = 'default')
    {
        return $this->connection($connection)->getSchemaBuilder();
    }
}

class ManyToManyPendingAttributesPost extends Model
{
    protected $guarded = [];
    protected $table = 'pending_attributes_posts';

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(
            ManyToManyPendingAttributesTag::class,
            'pending_attributes_pivot',
            'tag_id',
            'post_id',
        );
    }

    public function metaTags(): BelongsToMany
    {
        return $this->tags()
            ->withAttributes('visible', true, asConditions: false)
            ->withPivotValue('type', 'meta');
    }

    public function morphedTags(): MorphToMany
    {
        return $this
            ->morphToMany(
                ManyToManyPendingAttributesTag::class,
                'taggable',
                'pending_attributes_taggables',
                relatedPivotKey: 'tag_id'
            )
            ->withAttributes('visible', true, asConditions: false)
            ->withPivotValue('type', 'meta');
    }
}

class ManyToManyPendingAttributesTag extends Model
{
    protected $guarded = [];
    protected $table = 'pending_attributes_tags';

    public function morphedPosts(): MorphToMany
    {
        return $this
            ->morphedByMany(
                ManyToManyPendingAttributesPost::class,
                'taggable',
                'pending_attributes_taggables',
                'tag_id',
            )
            ->withAttributes('title', 'Title!', asConditions: false)
            ->withPivotValue('type', 'meta');
    }
}
