<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentBelongsToManySyncReturnValueTypeTest extends TestCase
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
            $table->string('id');
            $table->string('title');

            $table->primary('id');
        });

        $this->schema()->create('article_user', function ($table) {
            $table->string('article_id');
            $table->foreign('article_id')->references('id')->on('articles');
            $table->integer('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users');
            $table->boolean('visible')->default(false);
        });
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
        BelongsToManySyncTestTestUser::create(['id' => 1, 'email' => 'taylorotwell@gmail.com']);
        BelongsToManySyncTestTestArticle::insert([
            ['id' => '7b7306ae-5a02-46fa-a84c-9538f45c7dd4', 'title' => 'uuid title'],
            ['id' => (string) (PHP_INT_MAX + 1), 'title' => 'Another title'],
            ['id' => '1', 'title' => 'Another title'],
        ]);
    }

    public function testSyncReturnValueType()
    {
        $this->seedData();

        $user = BelongsToManySyncTestTestUser::query()->first();
        $articleIDs = BelongsToManySyncTestTestArticle::all()->pluck('id')->toArray();

        $changes = $user->articles()->sync($articleIDs);

        collect($changes['attached'])->map(function ($id) {
            $this->assertSame(gettype($id), (new BelongsToManySyncTestTestArticle)->getKeyType());
        });

        $user->articles->each(function (BelongsToManySyncTestTestArticle $article) {
            $this->assertSame('0', (string) $article->pivot->visible);
        });
    }

    public function testSyncWithPivotDefaultsReturnValueType()
    {
        $this->seedData();

        $user = BelongsToManySyncTestTestUser::query()->first();
        $articleIDs = BelongsToManySyncTestTestArticle::all()->pluck('id')->toArray();

        $changes = $user->articles()->syncWithPivotValues($articleIDs, ['visible' => true]);

        collect($changes['attached'])->each(function ($id) {
            $this->assertSame(gettype($id), (new BelongsToManySyncTestTestArticle)->getKeyType());
        });

        $user->articles->each(function (BelongsToManySyncTestTestArticle $article) {
            $this->assertSame('1', (string) $article->pivot->visible);
        });
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

class BelongsToManySyncTestTestUser extends Eloquent
{
    protected $table = 'users';
    protected $fillable = ['id', 'email'];
    public $timestamps = false;

    public function articles()
    {
        return $this->belongsToMany(BelongsToManySyncTestTestArticle::class, 'article_user', 'user_id', 'article_id')->withPivot('visible');
    }
}

class BelongsToManySyncTestTestArticle extends Eloquent
{
    protected $table = 'articles';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = ['id', 'title'];
}
