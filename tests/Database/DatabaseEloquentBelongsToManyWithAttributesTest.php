<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentBelongsToManyWithAttributesTest extends TestCase
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

    public function testCreatesWithAttributesAndPivotValues(): void
    {
        $post = ManyToManyWithAttributesPost::create();
        $tag = $post->metaTags()->create(['name' => 'long article']);

        $this->assertSame('long article', $tag->name);
        $this->assertTrue($tag->visible);

        $pivot = DB::table('with_attributes_pivot')->first();
        $this->assertSame('meta', $pivot->type);
        $this->assertSame($post->id, $pivot->post_id);
        $this->assertSame($tag->id, $pivot->tag_id);
    }

    public function testQueriesWithAttributesAndPivotValues(): void
    {
        $post = new ManyToManyWithAttributesPost(['id' => 2]);
        $wheres = $post->metaTags()->toBase()->wheres;

        $this->assertContains([
            'type' => 'Basic',
            'column' => 'with_attributes_tags.visible',
            'operator' => '=',
            'value' => true,
            'boolean' => 'and',
        ], $wheres);

        $this->assertContains([
            'type' => 'Basic',
            'column' => 'with_attributes_pivot.type',
            'operator' => '=',
            'value' => 'meta',
            'boolean' => 'and',
        ], $wheres);
    }

    public function testMorphToManyWithAttributes(): void
    {
        $post = new ManyToManyWithAttributesPost(['id' => 2]);
        $wheres = $post->morphedTags()->toBase()->wheres;

        $this->assertContains([
            'type' => 'Basic',
            'column' => 'with_attributes_tags.visible',
            'operator' => '=',
            'value' => true,
            'boolean' => 'and',
        ], $wheres);

        $this->assertContains([
            'type' => 'Basic',
            'column' => 'with_attributes_taggables.type',
            'operator' => '=',
            'value' => 'meta',
            'boolean' => 'and',
        ], $wheres);

        $this->assertContains([
            'type' => 'Basic',
            'column' => 'with_attributes_taggables.taggable_type',
            'operator' => '=',
            'value' => ManyToManyWithAttributesPost::class,
            'boolean' => 'and',
        ], $wheres);

        $this->assertContains([
            'type' => 'Basic',
            'column' => 'with_attributes_taggables.taggable_id',
            'operator' => '=',
            'value' => 2,
            'boolean' => 'and',
        ], $wheres);

        $tag = $post->morphedTags()->create(['name' => 'new tag']);

        $this->assertTrue($tag->visible);
        $this->assertSame('new tag', $tag->name);
        $this->assertSame($tag->id, $post->morphedTags()->first()->id);
    }

    public function testMorphedByManyWithAttributes(): void
    {
        $tag = new ManyToManyWithAttributesTag(['id' => 4]);
        $wheres = $tag->morphedPosts()->toBase()->wheres;

        $this->assertContains([
            'type' => 'Basic',
            'column' => 'with_attributes_posts.title',
            'operator' => '=',
            'value' => 'Title!',
            'boolean' => 'and',
        ], $wheres);

        $this->assertContains([
            'type' => 'Basic',
            'column' => 'with_attributes_taggables.type',
            'operator' => '=',
            'value' => 'meta',
            'boolean' => 'and',
        ], $wheres);

        $this->assertContains([
            'type' => 'Basic',
            'column' => 'with_attributes_taggables.taggable_type',
            'operator' => '=',
            'value' => ManyToManyWithAttributesPost::class,
            'boolean' => 'and',
        ], $wheres);

        $this->assertContains([
            'type' => 'Basic',
            'column' => 'with_attributes_taggables.tag_id',
            'operator' => '=',
            'value' => 4,
            'boolean' => 'and',
        ], $wheres);

        $post = $tag->morphedPosts()->create();
        $this->assertSame('Title!', $post->title);
        $this->assertSame($post->id, $tag->morphedPosts()->first()->id);
    }

    protected function createSchema()
    {
        $this->schema()->create('with_attributes_posts', function ($table) {
            $table->increments('id');
            $table->string('title')->nullable();
            $table->timestamps();
        });

        $this->schema()->create('with_attributes_tags', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->boolean('visible')->nullable();
            $table->timestamps();
        });

        $this->schema()->create('with_attributes_pivot', function ($table) {
            $table->integer('post_id');
            $table->integer('tag_id');
            $table->string('type');
        });

        $this->schema()->create('with_attributes_taggables', function ($table) {
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
        $this->schema()->drop('with_attributes_posts');
        $this->schema()->drop('with_attributes_tags');
        $this->schema()->drop('with_attributes_pivot');
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

class ManyToManyWithAttributesPost extends Model
{
    protected $guarded = [];
    protected $table = 'with_attributes_posts';

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(
            ManyToManyWithAttributesTag::class,
            'with_attributes_pivot',
            'tag_id',
            'post_id',
        );
    }

    public function metaTags(): BelongsToMany
    {
        return $this->tags()
            ->withAttributes('visible', true)
            ->withPivotValue('type', 'meta');
    }

    public function morphedTags(): MorphToMany
    {
        return $this
            ->morphToMany(
                ManyToManyWithAttributesTag::class,
                'taggable',
                'with_attributes_taggables',
                relatedPivotKey: 'tag_id'
            )
            ->withAttributes('visible', true)
            ->withPivotValue('type', 'meta');
    }
}

class ManyToManyWithAttributesTag extends Model
{
    protected $guarded = [];
    protected $table = 'with_attributes_tags';

    public function morphedPosts(): MorphToMany
    {
        return $this
            ->morphedByMany(
                ManyToManyWithAttributesPost::class,
                'taggable',
                'with_attributes_taggables',
                'tag_id',
            )
            ->withAttributes('title', 'Title!')
            ->withPivotValue('type', 'meta');
    }
}
