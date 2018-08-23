<?php

namespace Illuminate\Tests\Integration\Events;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Contracts\Events\Dispatcher;

class ModelDispatcherTest extends TestCase
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
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'username' => 'root',
            'password' => '',
            'database' => 'forge',
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

    public function testModelDispatcherAdheresToTheEventDispatcherContract()
    {
        $this->app->singleton('events', function () {
            return new MyCustomEventDispatcher();
        });
        Model::setEventDispatcher($this->app['events']);
        TestPost::observe([TestPostObserver::class]);

        $post = new TestPost();
        $post->title = 'xyz';
        $post->save();

        $this->assertSame('xyz-Test', $post->slug);
    }
}

class MyCustomEventDispatcher implements Dispatcher
{
    /**
     * The IoC container instance.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * The registered event listeners.
     *
     * @var array
     */
    protected $listeners = [];

    /**
     * The cached wildcard listeners.
     *
     * @var array
     */
    protected $wildcardsCache = [];

    /**
     * Create a new event dispatcher instance.
     *
     * @param  \Illuminate\Contracts\Container\Container|null  $container
     * @return void
     */
    public function __construct(Container $container = null)
    {
        $this->container = $container ?: new Container;
    }

    public function listen($events, $listener)
    {
        foreach ((array) $events as $event) {
            $this->listeners[$event][] = $this->makeListener($listener);
        }
    }

    public function hasListeners($eventName)
    {
        throw new Exception('The hasListeners method should not have been called.');
    }

    public function subscribe($subscriber)
    {
        throw new Exception('The subscribe should not have been called.');
    }

    public function until($event, $payload = [])
    {
        return $this->dispatch($event, $payload, true);
    }

    public function fire($event, $payload = [], $halt = false)
    {
        throw new Exception('The fire method should not have been called.');
    }

    public function dispatch($event, $payload = [], $halt = false)
    {
        list($event, $payload) = $this->parseEventAndPayload(
            $event, $payload
        );

        $responses = [];

        foreach ($this->getListeners($event) as $listener) {
            $response = $listener($event, $payload);
            $responses[] = $response;
        }

        return $halt ? null : $responses;
    }

    public function push($event, $payload = [])
    {
        throw new Exception('The push method should not have been called.');
    }

    public function flush($event)
    {
        throw new Exception('The flush method should not have been called.');
    }

    public function forget($event)
    {
        throw new Exception('The forget should not have been called.');
    }

    public function forgetPushed()
    {
        throw new Exception('The forgetPushed should not have been called.');
    }

    /**
     * Parse the given event and payload and prepare them for dispatching.
     *
     * @param  mixed  $event
     * @param  mixed  $payload
     * @return array
     */
    protected function parseEventAndPayload($event, $payload)
    {
        return [$event, Arr::wrap($payload)];
    }

    /**
     * Get all of the listeners for a given event name.
     *
     * @param  string  $eventName
     * @return array
     */
    public function getListeners($eventName)
    {
        $listeners = $this->listeners[$eventName] ?? [];

        $listeners = array_merge(
            $listeners,
            $this->wildcardsCache[$eventName] ?? $this->getWildcardListeners($eventName)
        );

        return $listeners;
    }

    /**
     * Get the wildcard listeners for the event.
     *
     * @param  string  $eventName
     * @return array
     */
    protected function getWildcardListeners($eventName)
    {
        $wildcards = [];

        return $this->wildcardsCache[$eventName] = $wildcards;
    }

    /**
     * Register an event listener with the dispatcher.
     *
     * @param  \Closure|string  $listener
     * @param  bool  $wildcard
     * @return \Closure
     */
    public function makeListener($listener, $wildcard = false)
    {
        return $this->createClassListener($listener, $wildcard);
    }

    /**
     * Create a class based listener using the IoC container.
     *
     * @param  string  $listener
     * @param  bool  $wildcard
     * @return \Closure
     */
    public function createClassListener($listener, $wildcard = false)
    {
        return function ($event, $payload) use ($listener, $wildcard) {
            return call_user_func_array(
                $this->createClassCallable($listener), $payload
            );
        };
    }

    /**
     * Create the class based event callable.
     *
     * @param  string  $listener
     * @return callable
     */
    protected function createClassCallable($listener)
    {
        list($class, $method) = $this->parseClassCallable($listener);

        return [$this->container->make($class), $method];
    }

    /**
     * Parse the class listener into class and method.
     *
     * @param  string  $listener
     * @return array
     */
    protected function parseClassCallable($listener)
    {
        return Str::parseCallback($listener, 'handle');
    }
}

class TestPost extends Model
{
    public $table = 'posts';
}

class TestPostObserver
{
    public function saving(TestPost $post)
    {
        $post->slug = sprintf('%s-Test', $post->title);
    }
}
