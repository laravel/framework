<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model as Eloquent;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentBelongsToManyChunkByIdTest extends TestCase
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

    /**
     * Setup the database schema.
     *
     * @return void
     */
    public function createSchema()
    {
        $this->schema()->create('users', function ($table) {
            $table->increments('id');
            $table->string('email')->unique();
        });

        $this->schema()->create('articles', function ($table) {
            $table->increments('id');
            $table->string('title');
        });

        $this->schema()->create('article_user', function ($table) {
            $table->increments('id');
            $table->integer('article_id')->unsigned();
            $table->foreign('article_id')->references('id')->on('articles');
            $table->integer('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    public function testBelongsToChunkById()
    {
        $this->seedData();

        $user = BelongsToManyChunkByIdTestTestUser::query()->first();
        $i = 0;

        $user->articles()->chunkById(1, function (Collection $collection) use (&$i) {
            $i++;
            $this->assertEquals($i, $collection->first()->id);
        });

        $this->assertSame(3, $i);
    }

    public function testBelongsToChunkByIdDesc()
    {
        $this->seedData();

        $user = BelongsToManyChunkByIdTestTestUser::query()->first();
        $i = 0;

        $user->articles()->chunkByIdDesc(1, function (Collection $collection) use (&$i) {
            $this->assertEquals(3 - $i, $collection->first()->id);
            $i++;
        });

        $this->assertSame(3, $i);
    }

    public function testBelongsToChunkByIdWithLastId()
    {
        $this->seedData();

        $user = BelongsToManyChunkByIdTestTestUser::query()->first();
        $i = 0;

        // Start chunking from ID 1, so it should skip ID 1 and start from ID 2
        $user->articles()->chunkById(1, function (Collection $collection) use (&$i) {
            $i++;
            // First chunk should start from ID 2
            if ($i === 1) {
                $this->assertEquals(2, $collection->first()->id);
            } else {
                $this->assertEquals(3, $collection->first()->id);
            }
        }, null, null, 1);

        // Should process 2 chunks (ID 2 and ID 3), skipping ID 1
        $this->assertSame(2, $i);
    }

    public function testBelongsToChunkByIdDescWithLastId()
    {
        $this->seedData();

        $user = BelongsToManyChunkByIdTestTestUser::query()->first();
        $i = 0;

        // Start chunking from ID 2 (descending), so it should skip ID 3 and start from ID 2
        $user->articles()->chunkByIdDesc(1, function (Collection $collection) use (&$i) {
            $i++;
            // First chunk should start from ID 2
            if ($i === 1) {
                $this->assertEquals(2, $collection->first()->id);
            } else {
                $this->assertEquals(1, $collection->first()->id);
            }
        }, null, null, 2);

        // Should process 2 chunks (ID 2 and ID 1), skipping ID 3
        $this->assertSame(2, $i);
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->schema()->drop('users');
        $this->schema()->drop('articles');
        $this->schema()->drop('article_user');
    }

    /**
     * Helpers...
     */
    protected function seedData()
    {
        $user = BelongsToManyChunkByIdTestTestUser::create(['id' => 1, 'email' => 'taylorotwell@gmail.com']);
        BelongsToManyChunkByIdTestTestArticle::query()->insert([
            ['id' => 1, 'title' => 'Another title'],
            ['id' => 2, 'title' => 'Another title'],
            ['id' => 3, 'title' => 'Another title'],
        ]);

        $user->articles()->sync([3, 1, 2]);
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

class BelongsToManyChunkByIdTestTestUser extends Eloquent
{
    protected $table = 'users';
    protected $fillable = ['id', 'email'];
    public $timestamps = false;

    public function articles()
    {
        return $this->belongsToMany(BelongsToManyChunkByIdTestTestArticle::class, 'article_user', 'user_id', 'article_id');
    }
}

class BelongsToManyChunkByIdTestTestArticle extends Eloquent
{
    protected $table = 'articles';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = ['id', 'title'];
}
