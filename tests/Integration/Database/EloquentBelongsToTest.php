<?php

namespace Illuminate\Tests\Integration\Database\EloquentBelongsToTest;

use Illuminate\Support\Carbon;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;

/**
 * @group integration
 */
class EloquentBelongsToTest extends TestCase
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

        Schema::create('authors', function ($table) {
            $table->increments('id');
            $table->integer('post_id');
            $table->string('name');
            $table->timestamps();
        });

        Carbon::setTestNow(null);
    }

    /**
     * @test
     */
    public function query_is_limited_on_lazy_loading()
    {
        Carbon::setTestNow(
            Carbon::createFromFormat('Y-m-d H:i:s', '2017-10-10 10:10:10')
        );

        $post = Post::create(['title' => str_random()]);

        $post->author()->create(['name' => str_random()]);

        $author = Author::find(1);

        \DB::enableQueryLog();

        $author->load('post');

        $logs = \DB::getQueryLog();

        $this->assertContains('limit 1', $logs[0]['query']);
    }

    /**
     * @test
     */
    public function query_is_limited_on_eager_loading()
    {
        Carbon::setTestNow(
            Carbon::createFromFormat('Y-m-d H:i:s', '2017-10-10 10:10:10')
        );

        $post = Post::create(['title' => str_random()]);

        $post->author()->create(['name' => str_random()]);

        \DB::enableQueryLog();

        Author::with('post')->get();

        $logs = \DB::getQueryLog();

        $this->assertContains('limit 1', $logs[1]['query']);
    }
}

class Post extends Model
{
    public $table = 'posts';
    public $timestamps = true;
    protected $guarded = ['id'];

    public function author()
    {
        return $this->hasOne(Author::class);
    }
}

class Author extends Model
{
    public $table = 'authors';
    public $timestamps = true;
    protected $guarded = ['id'];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}
