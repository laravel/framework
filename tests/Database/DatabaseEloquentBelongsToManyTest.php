<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentBelongsToManyTest extends TestCase
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
            $table->id('id');
        });

        $this->schema()->create('articles', function ($table) {
            $table->id('id');

            $table->string('title');
            $table->string('description');
        });

        $this->schema()->create('article_user', function ($table) {
            $table->foreignId('article_id')->references('id')->on('articles');
            $table->foreignId('user_id')->references('id')->on('users');
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

    public function testCreateWithDesiredAttributesUsingUpdateOrCreate()
    {
        /** @var BelongsToManySyncTestTestUser $user */
        $user = BelongsToManySyncTestTestUser::create();

        $user->articles()->updateOrCreate(
            ['title' => 'Fixing UpdateOrCreate'],
            ['description' => 'Fixed']
        );

        $article = $user->articles()->first();

        $this->assertSame($article->description, 'Fixed');
    }

    public function testUpdateWithDesiredAttributesUsingUpdateOrCreate()
    {
        /** @var BelongsToManySyncTestTestUser $user */
        $user = BelongsToManySyncTestTestUser::create();
        $user->articles()->create([
            'title' => $title = 'Fixing UpdateOrCreate',
            'description' => 'Not fixed',
        ]);

        $user->articles()->updateOrCreate(
            ['title' => $title],
            ['description' => 'Fixed']
        );

        $article = $user->articles()->first();

        $this->assertSame($article->description, 'Fixed');
    }

    /**
     * Get a database connection instance.
     *
     * @return \Illuminate\Database\ConnectionInterface
     */
    protected function connection()
    {
        return Model::getConnectionResolver()->connection();
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

class BelongsToManySyncTestTestUser extends Model
{
    protected $table = 'users';

    protected $fillable = ['email'];

    public $timestamps = false;

    public function articles()
    {
        return $this->belongsToMany(BelongsToManySyncTestTestArticle::class, 'article_user', 'user_id', 'article_id');
    }
}

class BelongsToManySyncTestTestArticle extends Model
{
    protected $table = 'articles';

    public $timestamps = false;

    protected $fillable = ['title', 'description'];
}
