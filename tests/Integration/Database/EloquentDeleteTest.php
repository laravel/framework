<?php

namespace Illuminate\Tests\Integration\Database;

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;

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
    }

    public function testOnlyDeleteWhatGiven()
    {
        for ($i = 1; $i <= 10; $i++) {
            Comment::create([
                'post_id' => Post::create()->id,
            ]);
        }

        Post::latest('id')->limit(1)->delete();
        $this->assertEquals(9, Post::all()->count());

        Post::join('comments', 'comments.post_id', '=', 'posts.id')->where('posts.id', '>', 1)->orderBy('posts.id')->limit(1)->delete();
        $this->assertEquals(8, Post::all()->count());
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
