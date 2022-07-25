<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentBelongsToManyFirstOrNewTest extends TestCase
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
            $table->increments('aid');
            $table->string('title');
        });

        $this->schema()->create('article_user', function ($table) {
            $table->integer('article_id')->unsigned();
            $table->foreign('article_id')->references('aid')->on('articles');
            $table->integer('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    public function testBelongsToManyFirstOrNew()
    {
        $this->seedData();

        $user2 = BelongsToManyFirstOrNewTestUser::query()->where('id',2)->first();

        $this->assertSame($user2->articles()->first()->aid, $user2->articles()->firstOrNew()->aid);

        $user3 = BelongsToManyFirstOrNewTestUser::query()->where('id',3)->first();
        $this->assertInstanceOf(BelongsToManyFirstOrNewTestArticle::class, $user3->articles()->firstOrNew());
        $this->assertNull($user3->articles()->firstOrNew()->aid);


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
        $user = BelongsToManyFirstOrNewTestUser::create(['id' => 1, 'email' => 'user1@gmail.com']);
        $user2 = BelongsToManyFirstOrNewTestUser::create(['id' => 2, 'email' => 'user2@gmail.com']);
        BelongsToManyFirstOrNewTestUser::create(['id' => 3, 'email' => 'user3@gmail.com']);

        BelongsToManyFirstOrNewTestArticle::query()->insert([
            ['aid' => 1, 'title' => 'Article of user1'],
            ['aid' => 2, 'title' => 'Article of user2'],
            ['aid' => 3, 'title' => 'Another article of user2'],
        ]);

        $user->articles()->sync([1]);
        $user2->articles()->sync([2, 3]);
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

class BelongsToManyFirstOrNewTestUser extends Eloquent
{
    protected $table = 'users';
    protected $fillable = ['id', 'email'];
    public $timestamps = false;

    public function articles()
    {
        return $this->belongsToMany(BelongsToManyFirstOrNewTestArticle::class, 'article_user', 'user_id', 'article_id');
    }
}

class BelongsToManyFirstOrNewTestArticle extends Eloquent
{
    protected $primaryKey = 'aid';
    protected $table = 'articles';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = ['aid', 'title'];
}
