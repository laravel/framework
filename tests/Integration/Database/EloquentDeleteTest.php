<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\Fixtures\Post;

class EloquentDeleteTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title')->nullable();
            $table->timestamps();
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('body')->nullable();
            $table->integer('post_id');
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function testDeleteWithLimit()
    {
        if ($this->driver === 'sqlsrv') {
            $this->markTestSkipped('The limit keyword is not supported on MSSQL.');
        }

        for ($i = 1; $i <= 10; $i++) {
            Comment::create([
                'post_id' => Post::create()->id,
            ]);
        }

        Post::latest('id')->limit(1)->delete();
        $this->assertCount(9, Post::all());

        Post::join('comments', 'comments.post_id', '=', 'posts.id')
            ->where('posts.id', '>', 8)
            ->orderBy('posts.id')
            ->limit(1)
            ->delete();
        $this->assertCount(8, Post::all());
    }

    public function testForceDeletedEventIsFired()
    {
        $role = Role::create([]);
        $this->assertInstanceOf(Role::class, $role);
        Role::observe(new RoleObserver);

        $role->delete();
        $this->assertNull(RoleObserver::$model);

        $role->forceDelete();

        $this->assertEquals($role->id, RoleObserver::$model->id);
    }

    public function testDeleteQuietly()
    {
        $_SERVER['(-_-)'] = '\(^_^)/';
        Post::deleting(fn () => $_SERVER['(-_-)'] = null);
        Post::deleted(fn () => $_SERVER['(-_-)'] = null);
        $post = Post::query()->create([]);
        $result = $post->deleteQuietly();

        $this->assertEquals('\(^_^)/', $_SERVER['(-_-)']);
        $this->assertTrue($result);
        $this->assertFalse($post->exists);

        // For a soft-deleted model:
        Role::deleting(fn () => $_SERVER['(-_-)'] = null);
        Role::deleted(fn () => $_SERVER['(-_-)'] = null);
        Role::softDeleted(fn () => $_SERVER['(-_-)'] = null);
        $role = Role::create([]);
        $result = $role->deleteQuietly();
        $this->assertTrue($result);
        $this->assertEquals('\(^_^)/', $_SERVER['(-_-)']);

        unset($_SERVER['(-_-)']);
    }
}

class Comment extends Model
{
    public $table = 'comments';
    protected $fillable = ['post_id'];
}

class Role extends Model
{
    use SoftDeletes;
    public $table = 'roles';
    protected $guarded = [];
}

class RoleObserver
{
    public static $model;

    public function forceDeleted($model)
    {
        static::$model = $model;
    }
}
