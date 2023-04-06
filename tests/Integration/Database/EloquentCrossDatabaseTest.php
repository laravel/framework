<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\MySql\MySqlTestCase;

class EloquentCrossDatabaseTest extends MySqlTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'conn1');

        $app['config']->set('database.connections.conn1', [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'username' => 'root',
            'password' => '',
            'database' => 'forge_one',
            'prefix' => '',
        ]);

        $app['config']->set('database.connections.conn2', [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'username' => 'root',
            'password' => '',
            'database' => 'forge_two',
            'prefix' => '',
        ]);

        parent::getEnvironmentSetUp($app);
    }

    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        tap(Schema::connection('conn1'), function ($schema) {
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

        tap(Schema::connection('conn2'), function ($schema) {
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

        tap(DB::connection('conn1'), function ($db) {
            $db->table('posts')->insert([
                ['title' => 'Foobar', 'user_id' => 1],
                ['title' => 'The title', 'user_id' => 1],
            ]);
        });

        tap(DB::connection('conn2'), function ($db) {
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
            Schema::connection('conn2')->dropIfExists($table);
        }
    }

    public function testRelationships()
    {
        ($db1 = DB::connection('conn1'))->enableQueryLog();
        ($db2 = DB::connection('conn2'))->enableQueryLog();

        foreach (['user', 'comments', 'tags', 'view'] as $relation) {
            $this->assertInstanceOf(Collection::class, Post::query()->with($relation)->get());
            $this->assertInstanceOf(Collection::class, Post::query()->withCount($relation)->get());
            $this->assertInstanceOf(Collection::class, Post::query()->whereHas($relation)->get());
        }

        collect(array_merge($db1->getQueryLog(), $db2->getQueryLog()))->each(fn($i) => dump($i['query']));
    }
}

abstract class BaseModel extends Model
{
    public $timestamps = false;
    protected $guarded = [];
}

abstract class Conn2BaseModel extends BaseModel
{
    protected $connection = 'conn2';
}

class Post extends BaseModel
{
    protected $connection = 'conn1';

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

class User extends Conn2BaseModel
{
    //
}

class SubPost extends Conn2BaseModel
{
    //
}

class Comment extends Conn2BaseModel
{
    //
}

class Tag extends Conn2BaseModel
{
    //
}

class View extends Conn2BaseModel
{
    //
}
