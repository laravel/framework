<?php

namespace Illuminate\Tests\Integration\Events;

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;

class EventFakeTest extends TestCase
{
    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application   $app
     *
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.debug', 'true');

        // Database configuration
        $app['config']->set('database.default', 'testbench');

        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();

        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->string('slug')->unique();
            $table->timestamps();
        });
    }

    /**
     * Clean up the testing environment before the next test.
     *
     * @return void
     */
    protected function tearDown()
    {
        Schema::dropIfExists('posts');

        parent::tearDown();
    }

    public function testNonFakedEventGetsProperlyDispatched()
    {
        Event::fake(NonImportantEvent::class);
        Post::observe([PostObserver::class]);

        $post = new Post();
        $post->title = 'xyz';
        $post->save();

        $this->assertSame('xyz-Test', $post->slug);

        Event::assertNotDispatched(NonImportantEvent::class);
    }
}

class Post extends Model
{
    public $table = 'posts';
}

class NonImportantEvent
{
}

class PostObserver
{
    public function saving(Post $post)
    {
        $post->slug = sprintf('%s-Test', $post->title);
    }
}
