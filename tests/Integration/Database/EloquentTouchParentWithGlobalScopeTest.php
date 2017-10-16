<?php

namespace Illuminate\Tests\Integration\Database\EloquentTouchParentWithGlobalScopeTest;

use Illuminate\Support\Carbon;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;

/**
 * @group integration
 */
class EloquentTouchParentWithGlobalScopeTest extends TestCase
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
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('comments', function ($table) {
            $table->increments('id');
            $table->integer('post_id');
            $table->string('title');
            $table->timestamps();
        });

        Carbon::setTestNow(null);
    }

    /**
     * @test
     */
    public function basic_create_and_retrieve()
    {
        $post = Post::create(['title' => str_random(), 'updated_at' => '2016-10-10 10:10:10']);

        $this->assertEquals('2016-10-10', $post->fresh()->updated_at->toDateString());

        $post->comments()->create(['title' => str_random()]);

        $this->assertNotEquals('2016-10-10', $post->fresh()->updated_at->toDateString());
    }
}

class Post extends Model
{
    public $table = 'posts';
    public $timestamps = true;
    protected $guarded = ['id'];

    public function comments()
    {
        return $this->hasMany(Comment::class, 'post_id');
    }

    public static function boot()
    {
        parent::boot();

        static::addGlobalScope('age', function ($builder) {
            $builder->join('comments', 'comments.post_id', '=', 'posts.id');
        });
    }
}

class Comment extends Model
{
    public $table = 'comments';
    public $timestamps = true;
    protected $guarded = ['id'];
    protected $touches = ['post'];

    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }
}
