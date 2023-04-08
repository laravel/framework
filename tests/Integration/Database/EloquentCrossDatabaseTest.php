<?php

namespace Illuminate\Tests\Integration\Database\EloquentCrossDatabaseTest;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\MySql\MySqlTestCase;

/**
 * @requires extension pdo_mysql
 * @requires OS Linux|Darwin
 */
class EloquentCrossDatabaseTest extends MySqlTestCase
{
    public const DEFAULT_CONNECTION = 'mysql';

    public const SECONDARY_CONNECTION = 'mysql2';

    protected function getEnvironmentSetUp($app)
    {
        if ($app['config']->get('database.default') !== 'mysql') {
            $this->markTestSkipped('Test requires a MySQL connection.');
        }

        // Create a second connection based on the first connection, but with a different database.
        $app['config']->set('database.connections.'.self::SECONDARY_CONNECTION, array_merge(
            $app['config']->get('database.connections.'.self::DEFAULT_CONNECTION),
            ['database' => 'forge_two']
        ));

        parent::getEnvironmentSetUp($app);
    }

    protected function setUpDatabaseRequirements(Closure $callback): void
    {
        $db = $this->app['config']->get('database.connections.'.self::SECONDARY_CONNECTION.'.database');
        try {
            $this->app['db']->connection()->statement('CREATE DATABASE '.$db);
        } catch(QueryException $e) {
            // ...
        }

        parent::setUpDatabaseRequirements($callback);
    }

    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        tap(Schema::connection(self::DEFAULT_CONNECTION), function ($schema) {
            try {
                $schema->create('posts', function (Blueprint $table) {
                    $table->increments('id');
                    $table->string('title');
                    $table->foreignId('user_id')->nullable();
                });
            } catch (QueryException $e) {
                //
            }
        });

        tap(Schema::connection(self::SECONDARY_CONNECTION), function ($schema) {
            try {
                $schema->create('users', function (Blueprint $table) {
                    $table->increments('id');
                    $table->string('username');
                });

                $schema->create('sub_posts', function (Blueprint $table) {
                    $table->increments('id');
                    $table->string('title');
                    $table->foreignId('post_id');
                });

                $schema->create('views', function (Blueprint $table) {
                    $table->increments('id');
                    $table->integer('hits')->default(1);
                    $table->morphs('viewable');
                });

                $schema->create('comments', function (Blueprint $table) {
                    $table->increments('id');
                    $table->string('content');
                    $table->foreignId('sub_post_id');
                });

                $schema->create('tags', function (Blueprint $table) {
                    $table->increments('id');
                    $table->string('tag');
                });

                $schema->create('post_tag', function (Blueprint $table) {
                    $table->foreignId('post_id');
                    $table->foreignId('tag_id');
                });
            } catch (QueryException $e) {
                //
            }
        });

        tap(DB::connection(self::DEFAULT_CONNECTION), function ($db) {
            $db->table('posts')->insert([
                ['title' => 'Foobar', 'user_id' => 1],
                ['title' => 'The title', 'user_id' => 1],
            ]);
        });

        tap(DB::connection(self::SECONDARY_CONNECTION), function ($db) {
            $db->table('users')->insert([
                ['username' => 'Lortay Wellot'],
            ]);

            $db->table('sub_posts')->insert([
                ['title' => 'The subpost title', 'post_id' => 1],
            ]);

            $db->table('comments')->insert([
                ['content' => 'The comment content', 'sub_post_id' => 1],
            ]);

            $db->table('views')->insert([
                ['hits' => 123, 'viewable_id' => 1, 'viewable_type' => Post::class],
            ]);
        });
    }

    protected function destroyDatabaseMigrations()
    {
        Schema::dropIfExists('posts');

        foreach (['users', 'sub_posts', 'comments', 'views', 'tags', 'post_tag'] as $table) {
            Schema::connection(self::SECONDARY_CONNECTION)->dropIfExists($table);
        }
    }

    public function testRelationships()
    {
        ($db1 = DB::connection(self::DEFAULT_CONNECTION))->enableQueryLog();
        ($db2 = DB::connection(self::SECONDARY_CONNECTION))->enableQueryLog();

        foreach (['user', 'comments', 'tags', 'view'] as $relation) {
            $this->assertInstanceOf(Collection::class, Post::query()->with($relation)->get());
            $this->assertInstanceOf(Collection::class, Post::query()->withCount($relation)->get());
            $this->assertInstanceOf(Collection::class, Post::query()->whereHas($relation)->get());
        }

        // @TODO debug code
        collect(array_merge($db1->getQueryLog(), $db2->getQueryLog()))->each(fn ($i) => dump($i['query']));
    }
}

abstract class BaseModel extends Model
{
    public $timestamps = false;
    protected $guarded = [];
}

abstract class SecondaryBaseModel extends BaseModel
{
    protected $connection = EloquentCrossDatabaseTest::SECONDARY_CONNECTION;
}

class Post extends BaseModel
{
    protected $connection = EloquentCrossDatabaseTest::DEFAULT_CONNECTION;

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comments()
    {
        return $this->hasManyThrough(Comment::class, SubPost::class, 'post_id', 'id');
    }

    public function view()
    {
        return $this->morphOne(View::class, 'viewable');
    }
}

class User extends SecondaryBaseModel
{
    //
}

class SubPost extends SecondaryBaseModel
{
    //
}

class Comment extends SecondaryBaseModel
{
    //
}

class Tag extends SecondaryBaseModel
{
    //
}

class View extends SecondaryBaseModel
{
    //
}
