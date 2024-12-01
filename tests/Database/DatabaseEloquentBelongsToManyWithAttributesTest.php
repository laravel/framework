<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    protected function createSchema()
    {
        $this->schema()->create('with_attributes_posts', function ($table) {
            $table->increments('id');
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
}

class ManyToManyWithAttributesTag extends Model
{
    protected $guarded = [];
    protected $table = 'with_attributes_tags';
}
