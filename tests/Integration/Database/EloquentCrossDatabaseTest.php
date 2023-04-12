<?php

namespace Illuminate\Tests\Integration\Database\EloquentCrossDatabaseTest;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class EloquentCrossDatabaseTest extends DatabaseTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        if (! in_array($default = $app['config']->get('database.default'), ['mysql', 'sqlsrv'])) {
            $this->markTestSkipped("Cross database queries not supported for $default.");
        }

        define('__TEST_DEFAULT_CONNECTION', $default);
        define('__TEST_SECONDARY_CONNECTION', $default.'_two');

        // Create a second connection based on the first connection, but with a different database.
        $app['config']->set('database.connections.'.__TEST_SECONDARY_CONNECTION, array_merge(
            $app['config']->get('database.connections.'.__TEST_DEFAULT_CONNECTION),
            ['database' => 'forge_two']
        ));

        parent::getEnvironmentSetUp($app);
    }

    protected function setUpDatabaseRequirements(Closure $callback): void
    {
        $db = $this->app['config']->get('database.connections.'.__TEST_SECONDARY_CONNECTION.'.database');
        try {
            $this->app['db']->connection()->statement('CREATE DATABASE '.$db);
        } catch(QueryException $e) {
            // ...
        }

        parent::setUpDatabaseRequirements($callback);
    }

    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        tap(Schema::connection(__TEST_DEFAULT_CONNECTION), function ($schema) {
            try {
                $schema->create('posts', function (Blueprint $table) {
                    $table->increments('id');
                    $table->string('title');
                    $table->foreignId('user_id')->nullable();
                    $table->foreignId('root_tag_id')->nullable();
                });
            } catch (QueryException $e) {
                //
            }
        });

        tap(Schema::connection(__TEST_SECONDARY_CONNECTION), function ($schema) {
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

                $schema->create('viewables', function (Blueprint $table) {
                    $table->foreignId('view_id');
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

        tap(DB::connection(__TEST_DEFAULT_CONNECTION), function ($db) {
            $db->table('posts')->insert([
                ['title' => 'Foobar', 'user_id' => 1],
                ['title' => 'The title', 'user_id' => 1],
            ]);
        });

        tap(DB::connection(__TEST_SECONDARY_CONNECTION), function ($db) {
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

        foreach (['users', 'sub_posts', 'comments', 'views', 'viewables', 'tags', 'post_tag'] as $table) {
            Schema::connection(__TEST_SECONDARY_CONNECTION)->dropIfExists($table);
        }
    }

    public function testRelationships()
    {
        // We only test general compilation without errors here, indicating that cross-database queries have been
        // executed correctly.
        foreach (['comments'] as $relation) {
//            $this->assertInstanceOf(Collection::class, Post::query()->with($relation)->get());
//            $this->assertInstanceOf(Collection::class, Post::query()->withCount($relation)->get());
//            $this->assertInstanceOf(Collection::class, Post::query()->whereHas($relation)->get());
        }

//        Post::query()->withCount('subPosts')->get();
        View::query()->with(['posts'])->get();
//        View::query()->with(['viewable'])->get();
    }
}

abstract class BaseModel extends Model
{
    public $timestamps = false;
    protected $guarded = [];
}

abstract class SecondaryBaseModel extends BaseModel
{
    protected $connection = __TEST_SECONDARY_CONNECTION;
}

class Post extends BaseModel
{
    protected $connection = __TEST_DEFAULT_CONNECTION;

    public function rootTag()
    {
        return $this->hasOne(Tag::class, 'id', 'root_tag_id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subPosts()
    {
        return $this->hasMany(SubPost::class);
    }

    public function comments()
    {
        return $this->hasManyThrough(Comment::class, SubPost::class, 'post_id', 'id');
    }

    public function view()
    {
        return $this->morphOne(View::class, 'viewable');
    }

    public function viewables()
    {
        return $this->morphToMany(View::class, 'viewable');
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
    public function viewable()
    {
        return $this->morphTo();
    }

    public function posts()
    {
        return $this->morphedByMany(Post::class, 'viewable');
    }
}
