<?php

use Mockery as m;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Auth\Access\Gate;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\Middleware\Authorize;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;

class FoundationAuthorizeMiddlewareTest extends PHPUnit_Framework_TestCase
{
    protected $container;
    protected $user;

    public function tearDown()
    {
        m::close();
    }

    public function setUp()
    {
        parent::setUp();

        $this->user = new stdClass;

        Container::setInstance($this->container = new Container);

        $this->container->singleton(Auth::class, function () {
            $auth = m::mock(Auth::class);
            $auth->shouldReceive('authenticate')->once()->andReturn(null);

            return $auth;
        });

        $this->container->singleton(GateContract::class, function () {
            return new Gate($this->container, function () {
                return $this->user;
            });
        });

        $this->router = new Router(new Dispatcher, $this->container);
    }

    public function testSimpleAbilityUnauthorized()
    {
        $this->setExpectedException(AuthorizationException::class);

        $this->gate()->define('view-dashboard', function ($user, $additional = null) {
            $this->assertNull($additional);

            return false;
        });

        $this->router->get('dashboard', [
            'middleware' => Authorize::class.':view-dashboard',
            'uses' => function () { return 'success'; },
        ]);

        $this->router->dispatch(Request::create('dashboard', 'GET'));
    }

    public function testSimpleAbilityAuthorized()
    {
        $this->gate()->define('view-dashboard', function ($user) {
            return true;
        });

        $this->router->get('dashboard', [
            'middleware' => Authorize::class.':view-dashboard',
            'uses' => function () { return 'success'; },
        ]);

        $response = $this->router->dispatch(Request::create('dashboard', 'GET'));

        $this->assertEquals($response->content(), 'success');
    }

    public function testModelTypeUnauthorized()
    {
        $this->setExpectedException(AuthorizationException::class);

        $this->gate()->define('create', function ($user, $model) {
            $this->assertEquals($model, 'App\User');

            return false;
        });

        $this->router->get('users/create', [
            'middleware' => Authorize::class.':create,App\User',
            'uses' => function () { return 'success'; },
        ]);

        $this->router->dispatch(Request::create('users/create', 'GET'));
    }

    public function testModelTypeAuthorized()
    {
        $this->gate()->define('create', function ($user, $model) {
            $this->assertEquals($model, 'App\User');

            return true;
        });

        $this->router->get('users/create', [
            'middleware' => Authorize::class.':create,App\User',
            'uses' => function () { return 'success'; },
        ]);

        $response = $this->router->dispatch(Request::create('users/create', 'GET'));

        $this->assertEquals($response->content(), 'success');
    }

    public function testModelUnauthorized()
    {
        $this->setExpectedException(AuthorizationException::class);

        $post = new stdClass;

        $this->router->bind('post', function () use ($post) { return $post; });

        $this->gate()->define('edit', function ($user, $model) use ($post) {
            $this->assertSame($model, $post);

            return false;
        });

        $this->router->get('posts/{post}/edit', [
            'middleware' => Authorize::class.':edit,post',
            'uses' => function () { return 'success'; },
        ]);

        $this->router->dispatch(Request::create('posts/1/edit', 'GET'));
    }

    public function testModelAuthorized()
    {
        $post = new stdClass;

        $this->router->bind('post', function () use ($post) { return $post; });

        $this->gate()->define('edit', function ($user, $model) use ($post) {
            $this->assertSame($model, $post);

            return true;
        });

        $this->router->get('posts/{post}/edit', [
            'middleware' => Authorize::class.':edit,post',
            'uses' => function () { return 'success'; },
        ]);

        $response = $this->router->dispatch(Request::create('posts/1/edit', 'GET'));

        $this->assertEquals($response->content(), 'success');
    }

    /**
     * Get the Gate instance from the container.
     *
     * @return \Illuminate\Auth\Access\Gate
     */
    protected function gate()
    {
        return $this->container->make(GateContract::class);
    }
}
