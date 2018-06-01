<?php

namespace Illuminate\Tests\Integration\Database;

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @group integration
 */
class EloquentDeleteTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.debug', 'true');

        $app['config']->set('database.default', 'testbench');

        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    public function setUp()
    {
        parent::setUp();

        Schema::create('posts', function ($table) {
            $table->increments('id');
            $table->string('title')->nullable();
            $table->timestamps();
        });

        Schema::create('comments', function ($table) {
            $table->increments('id');
            $table->string('body')->nullable();
            $table->integer('post_id');
            $table->timestamps();
        });

        Schema::create('roles', function ($table) {
            $table->increments('id');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function testOnlyDeleteWhatGiven()
    {
        for ($i = 1; $i <= 10; $i++) {
            Comment::create([
                'post_id' => Post::create()->id,
            ]);
        }

        Post::latest('id')->limit(1)->delete();
        $this->assertCount(9, Post::all());

        Post::join('comments', 'comments.post_id', '=', 'posts.id')->where('posts.id', '>', 1)->orderBy('posts.id')->limit(1)->delete();
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
}

class Post extends Model
{
    public $table = 'posts';
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
