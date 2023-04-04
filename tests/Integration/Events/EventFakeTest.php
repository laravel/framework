<?php

namespace Illuminate\Tests\Integration\Events;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Arr;
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

    public function testEventsListedInExceptAreProperlyDispatched()
    {
        Event::fake()->except('important-event');

        Event::listen('test', function () {
            return 'test';
        });

        Event::listen('important-event', function () {
            return 'important';
        });

        $this->assertEquals(null, Event::dispatch('test'));
        $this->assertEquals(['important'], Event::dispatch('important-event'));
    }

    public function testAssertListening()
    {
        Event::fake();

        $listenersOfSameEventInRandomOrder = Arr::shuffle([
            'listener',
            'Illuminate\\Tests\\Integration\\Events\\PostAutoEventSubscriber@handle',
            PostEventSubscriber::class,
            [PostEventSubscriber::class, 'foo'],
            InvokableEventSubscriber::class,
        ]);

        foreach ($listenersOfSameEventInRandomOrder as $listener) {
            Event::listen('event', $listener);
        }

        Event::subscribe(PostEventSubscriber::class);

        Event::listen(function (NonImportantEvent $event) {
            // do something
        });

        Post::observe(new PostObserver);

        ($post = new Post)->save();

        Event::assertListening('event', 'listener');
        Event::assertListening('event', PostEventSubscriber::class);
        Event::assertListening('event', PostAutoEventSubscriber::class);
        Event::assertListening('event', [PostEventSubscriber::class, 'foo']);
        Event::assertListening('post-created', [PostEventSubscriber::class, 'handlePostCreated']);
        Event::assertListening('post-deleted', [PostEventSubscriber::class, 'handlePostDeleted']);
        Event::assertListening(NonImportantEvent::class, Closure::class);
        Event::assertListening('eloquent.saving: '.Post::class, PostObserver::class.'@saving');
        Event::assertListening('eloquent.saving: '.Post::class, [PostObserver::class, 'saving']);
        Event::assertListening('event', InvokableEventSubscriber::class);
    }
}

class Post extends Model
{
    public $table = 'posts';

    public function save(array $options = [])
    {
        if ($this->fireModelEvent('saving') === false) {
            return false;
        }
    }
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

    public function handlePostDeleted($event)
    {
    }

    public function subscribe($events)
    {
        $events->listen(
            'post-created',
            [PostEventSubscriber::class, 'handlePostCreated']
        );

        $events->listen(
            'post-deleted',
            PostEventSubscriber::class.'@handlePostDeleted'
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

class InvokableEventSubscriber
{
    public function __invoke($event)
    {
        //
    }
}
