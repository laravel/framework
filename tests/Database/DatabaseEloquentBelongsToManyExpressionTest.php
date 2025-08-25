<?php

namespace Illuminate\Tests\Database;

use Exception;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentBelongsToManyExpressionTest extends TestCase
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

    public function testAmbiguousColumnsExpression(): void
    {
        $this->seedData();

        $tags = DatabaseEloquentBelongsToManyExpressionTestTestPost::findOrFail(1)
            ->tags()
            ->wherePivotNotIn(new Expression("tag_id || '_' || type"), ['1_t1'])
            ->get();

        $this->assertCount(1, $tags);
        $this->assertEquals(2, $tags->first()->getKey());
    }

    public function testQualifiedColumnExpression(): void
    {
        $this->seedData();

        $tags = DatabaseEloquentBelongsToManyExpressionTestTestPost::findOrFail(2)
            ->tags()
            ->wherePivotNotIn(new Expression("taggables.tag_id || '_' || taggables.type"), ['2_t2'])
            ->get();

        $this->assertCount(1, $tags);
        $this->assertEquals(3, $tags->first()->getKey());
    }

    public function testGlobalScopesAreAppliedToBelongsToManyRelation(): void
    {
        $this->seedData();
        $post = DatabaseEloquentBelongsToManyExpressionTestTestPost::query()->firstOrFail();
        DatabaseEloquentBelongsToManyExpressionTestTestTag::addGlobalScope(
            'default',
            static fn () => throw new Exception('Default global scope.')
        );

        $this->expectExceptionMessage('Default global scope.');
        $post->tags()->get();
    }

    public function testGlobalScopesCanBeRemovedFromBelongsToManyRelation(): void
    {
        $this->seedData();
        $post = DatabaseEloquentBelongsToManyExpressionTestTestPost::query()->firstOrFail();
        DatabaseEloquentBelongsToManyExpressionTestTestTag::addGlobalScope(
            'default',
            static fn () => throw new Exception('Default global scope.')
        );

        $this->assertNotEmpty($post->tags()->withoutGlobalScopes()->get());
    }

    /**
     * Setup the database schema.
     *
     * @return void
     */
    public function createSchema()
    {
        $this->schema()->create('posts', fn (Blueprint $t) => $t->id());
        $this->schema()->create('tags', fn (Blueprint $t) => $t->id());
        $this->schema()->create('taggables', function (Blueprint $t) {
            $t->unsignedBigInteger('tag_id');
            $t->unsignedBigInteger('taggable_id');
            $t->string('type', 10);
            $t->string('taggable_type');
        }
        );
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
        $this->schema()->drop('taggables');
    }

    /**
     * Helpers...
     */
    protected function seedData(): void
    {
        $p1 = DatabaseEloquentBelongsToManyExpressionTestTestPost::query()->create();
        $p2 = DatabaseEloquentBelongsToManyExpressionTestTestPost::query()->create();
        $t1 = DatabaseEloquentBelongsToManyExpressionTestTestTag::query()->create();
        $t2 = DatabaseEloquentBelongsToManyExpressionTestTestTag::query()->create();
        $t3 = DatabaseEloquentBelongsToManyExpressionTestTestTag::query()->create();

        $p1->tags()->sync([
            $t1->getKey() => ['type' => 't1'],
            $t2->getKey() => ['type' => 't2'],
        ]);
        $p2->tags()->sync([
            $t2->getKey() => ['type' => 't2'],
            $t3->getKey() => ['type' => 't3'],
        ]);
    }

    /**
     * Get a database connection instance.
     *
     * @return \Illuminate\Database\ConnectionInterface
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

class DatabaseEloquentBelongsToManyExpressionTestTestPost extends Eloquent
{
    protected $table = 'posts';
    protected $fillable = ['id'];
    public $timestamps = false;

    public function tags(): MorphToMany
    {
        return  $this->morphToMany(
            DatabaseEloquentBelongsToManyExpressionTestTestTag::class,
            'taggable',
            'taggables',
            'taggable_id',
            'tag_id',
            'id',
            'id',
        );
    }
}

class DatabaseEloquentBelongsToManyExpressionTestTestTag extends Eloquent
{
    protected $table = 'tags';
    protected $fillable = ['id'];
    public $timestamps = false;
}
