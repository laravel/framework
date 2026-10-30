<?php

declare(strict_types=1);

namespace Illuminate\Tests\Database;

use Illuminate\Database\ConnectionResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Tests\Database\Concerns\RestoresConnectionResolver;
use PDO;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentBelongsToManyWithoutTouchingTest extends TestCase
{
    use RestoresConnectionResolver;

    protected function newConnection(): SQLiteConnection
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('create table "users" ("id" integer primary key)');
        $pdo->exec('create table "articles" ("id" integer primary key, "title" text, "updated_at" text)');
        $pdo->exec('create table "article_user" ("user_id" integer, "article_id" integer)');
        $pdo->exec('insert into "users" values (1)');
        $pdo->exec("insert into \"articles\" values (1, 'title', null)");
        $pdo->exec('insert into "article_user" values (1, 1)');

        $connection = new SQLiteConnection($pdo);
        $resolver = new ConnectionResolver(['default' => $connection]);
        $resolver->setDefaultConnection('default');
        Model::setConnectionResolver($resolver);

        return $connection;
    }

    public function testItWillNotTouchRelatedModelsWhenUpdatingChild(): void
    {
        $connection = $this->newConnection();
        $user = new User(['id' => 1]);
        $builder = (new Builder($connection->query()))->setModel(new Article);
        $relation = new BelongsToMany($builder, $user, 'article_user', 'user_id', 'article_id', 'id', 'id');

        $this->assertFalse(Article::isIgnoringTouch());

        Model::withoutTouching(function () use ($relation) {
            $this->assertTrue(Article::isIgnoringTouch());

            $relation->touch();
        });

        $this->assertNull($connection->scalar('select "updated_at" from "articles"'));

        $relation->touch();

        $this->assertNotNull($connection->scalar('select "updated_at" from "articles"'));
    }
}

class User extends Model
{
    protected $table = 'users';
    protected $fillable = ['id', 'email'];

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'article_user', 'user_id', 'article_id');
    }
}

class Article extends Model
{
    protected $table = 'articles';
    protected $dateFormat = 'Y-m-d H:i:s';
    protected $fillable = ['id', 'title'];
    protected $touches = ['user'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'article_user', 'article_id', 'user_id');
    }
}
