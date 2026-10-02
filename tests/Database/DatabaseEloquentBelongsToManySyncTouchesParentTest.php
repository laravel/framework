<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\Pivot as EloquentPivot;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentBelongsToManySyncTouchesParentTest extends TestCase
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
        $this->schema()->create('articles', function ($table) {
            $table->string('id');
            $table->string('title');

            $table->primary('id');
            $table->timestamps();
        });

        $this->schema()->create('article_user', function ($table) {
            $table->string('article_id');
            $table->foreign('article_id')->references('id')->on('articles');
            $table->integer('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users');
            $table->timestamps();
        });

        $this->schema()->create('users', function ($table) {
            $table->increments('id');
            $table->string('email')->unique();
            $table->timestamps();
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
        DatabaseEloquentBelongsToManySyncTouchesParentTestTestUser::create(['id' => 1, 'email' => 'taylorotwell@gmail.com']);
        DatabaseEloquentBelongsToManySyncTouchesParentTestTestUser::create(['id' => 2, 'email' => 'anonymous@gmail.com']);
        DatabaseEloquentBelongsToManySyncTouchesParentTestTestUser::create(['id' => 3, 'email' => 'anoni-mous@gmail.com']);
    }

    public function testSyncWithDetachedValuesShouldTouch()
    {
        $this->seedData();

        Carbon::setTestNow('2021-07-19 10:13:14');
        $article = DatabaseEloquentBelongsToManySyncTouchesParentTestTestArticle::create(['id' => 1, 'title' => 'uuid title']);
        $article->users()->sync([1, 2, 3]);
        $this->assertSame('2021-07-19 10:13:14', $article->updated_at->format('Y-m-d H:i:s'));

        Carbon::setTestNow('2021-07-20 19:13:14');
        $result = $article->users()->sync([1, 2]);
        $this->assertCount(1, collect($result['detached']));
        $this->assertSame('3', (string) collect($result['detached'])->first());

        $article->refresh();
        $this->assertSame('2021-07-20 19:13:14', $article->updated_at->format('Y-m-d H:i:s'));

        $user1 = DatabaseEloquentBelongsToManySyncTouchesParentTestTestUser::find(1);
        $this->assertNotSame('2021-07-20 19:13:14', $user1->updated_at->format('Y-m-d H:i:s'));
        $user2 = DatabaseEloquentBelongsToManySyncTouchesParentTestTestUser::find(2);
        $this->assertNotSame('2021-07-20 19:13:14', $user2->updated_at->format('Y-m-d H:i:s'));
        $user3 = DatabaseEloquentBelongsToManySyncTouchesParentTestTestUser::find(3);
        $this->assertNotSame('2021-07-20 19:13:14', $user3->updated_at->format('Y-m-d H:i:s'));
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

class DatabaseEloquentBelongsToManySyncTouchesParentTestTestArticle extends Eloquent
{
    protected $table = 'articles';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['id', 'title'];

    public function users()
    {
        return $this
            ->belongsToMany(DatabaseEloquentBelongsToManySyncTouchesParentTestTestArticle::class, 'article_user', 'article_id', 'user_id')
            ->using(DatabaseEloquentBelongsToManySyncTouchesParentTestTestArticleUser::class)
            ->withTimestamps();
    }
}

class DatabaseEloquentBelongsToManySyncTouchesParentTestTestArticleUser extends EloquentPivot
{
    protected $table = 'article_user';
    protected $fillable = ['article_id', 'user_id'];
    protected $touches = ['article'];

    public function article()
    {
        return $this->belongsTo(DatabaseEloquentBelongsToManySyncTouchesParentTestTestArticle::class, 'article_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(DatabaseEloquentBelongsToManySyncTouchesParentTestTestUser::class, 'user_id', 'id');
    }
}

class DatabaseEloquentBelongsToManySyncTouchesParentTestTestUser extends Eloquent
{
    protected $table = 'users';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['id', 'email'];

    public function articles()
    {
        return $this
            ->belongsToMany(DatabaseEloquentBelongsToManySyncTouchesParentTestTestArticle::class, 'article_user', 'user_id', 'article_id')
            ->using(DatabaseEloquentBelongsToManySyncTouchesParentTestTestArticleUser::class)
            ->withTimestamps();
    }
}
