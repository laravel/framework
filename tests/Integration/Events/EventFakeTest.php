<?php

namespace Illuminate\Tests\Integration\Events;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;

class EventFakeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->string('slug')->unique();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('posts');

        parent::tearDown();
    }

    public function testNonFakedEventGetsProperlyDispatched()
    {
        Event::fake(NonImportantEvent::class);
        Post::observe([PostObserver::class]);

        $post = new Post;
        $post->title = 'xyz';
        $post->save();

        $this->assertSame('xyz-Test', $post->slug);

        Event::assertNotDispatched(NonImportantEvent::class);
    }

    public function testNonFakedEventGetsProperlyDispatchedAndReturnsResponses()
    {
        Event::fake(NonImportantEvent::class);
        Event::listen('test', function () {
            // one
        });
        Event::listen('test', function () {
            return 'two';
        });
        Event::listen('test', function () {
            //
        });

        $this->assertEquals([null, 'two', null], Event::dispatch('test'));

        Event::assertNotDispatched(NonImportantEvent::class);
    }

    public function testNonFakedEventGetsProperlyDispatchedAndCancelsFutureListeners()
    {
        Event::fake(NonImportantEvent::class);
        Event::listen('test', function () {
            // one
        });
        Event::listen('test', function () {
            return false;
        });
        Event::listen('test', function () {
            $this->fail('should not be called');
        });

        $this->assertEquals([null], Event::dispatch('test'));

        Event::assertNotDispatched(NonImportantEvent::class);
    }

    public function testNonFakedHaltedEventGetsProperlyDispatchedAndReturnsResponse()
    {
        Event::fake(NonImportantEvent::class);
        Event::listen('test', function () {
            // one
        });
        Event::listen('test', function () {
            return 'two';
        });
        Event::listen('test', function () {
            $this->fail('should not be called');
        });

        $this->assertSame('two', Event::until('test'));

        Event::assertNotDispatched(NonImportantEvent::class);
    }

    public function testFakeExceptAllowsGivenEventToBeDispatched()
    {
        Event::fakeExcept(NonImportantEvent::class);

        Event::dispatch(NonImportantEvent::class);

        Event::assertNotDispatched(NonImportantEvent::class);
    }

    public function testFakeExceptAllowsGivenEventsToBeDispatched()
    {
        Event::fakeExcept([
            NonImportantEvent::class,
            'non-fake-event',
        ]);

        Event::dispatch(NonImportantEvent::class);
        Event::dispatch('non-fake-event');

        Event::assertNotDispatched(NonImportantEvent::class);
        Event::assertNotDispatched('non-fake-event');
    }

    public function testAssertListening()
    {
        Event::fake();
        Event::listen('event', 'listener');
        Event::listen('event', PostEventSubscriber::class);
        Event::listen('event', 'Illuminate\\Tests\\Integration\\Events\\PostAutoEventSubscriber@handle');
        Event::listen('event', [PostEventSubscriber::class, 'foo']);
        Event::subscribe(PostEventSubscriber::class);
        Event::listen(function (NonImportantEvent $event) {
            // do something
        });

        Event::assertListening('event', 'listener');
        Event::assertListening('event', PostEventSubscriber::class);
        Event::assertListening('event', PostAutoEventSubscriber::class);
        Event::assertListening('event', [PostEventSubscriber::class, 'foo']);
        Event::assertListening('post-created', [PostEventSubscriber::class, 'handlePostCreated']);
        Event::assertListening(NonImportantEvent::class, Closure::class);
    }
}

class Post extends Model
{
    public $table = 'posts';
}

class NonImportantEvent
{
    //
}

class PostEventSubscriber
{
    public function handlePostCreated($event)
    {
    }

    public function subscribe($events)
    {
        $events->listen(
            'post-created',
            [PostEventSubscriber::class, 'handlePostCreated']
        );
    }
}

class PostAutoEventSubscriber
{
    public function handle($event)
    {
        //
    }
}

class PostObserver
{
    public function saving(Post $post)
    {
        $post->slug = sprintf('%s-Test', $post->title);
    }
}
