<?php

namespace Illuminate\Tests\Auth;

use stdClass;
use Mockery as m;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use PHPUnit\Framework\TestCase;
use Illuminate\Auth\Access\Gate;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;

class AuthorizeMiddlewareTest extends TestCase
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

        $this->container->singleton(Registrar::class, function () {
            return $this->router;
        });
    }

    /**
     * @expectedException \Illuminate\Auth\Access\AuthorizationException
     * @expectedExceptionMessage This action is unauthorized.
     */
    public function testSimpleAbilityUnauthorized()
    {
        $this->gate()->define('view-dashboard', function ($user, $additional = null) {
            $this->assertNull($additional);

            return false;
        });

        $this->router->get('dashboard', [
            'middleware' => Authorize::class.':view-dashboard',
            'uses' => function () {
                return 'success';
            },
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
            'uses' => function () {
                return 'success';
            },
        ]);

        $response = $this->router->dispatch(Request::create('dashboard', 'GET'));

        $this->assertEquals($response->content(), 'success');
    }

    /**
     * @expectedException \Illuminate\Auth\Access\AuthorizationException
     * @expectedExceptionMessage This action is unauthorized.
     */
    public function testModelTypeUnauthorized()
    {
        $this->gate()->define('create', function ($user, $model) {
            $this->assertEquals($model, 'App\User');

            return false;
        });

        $this->router->get('users/create', [
            'middleware' => [SubstituteBindings::class, Authorize::class.':create,App\User'],
            'uses' => function () {
                return 'success';
            },
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
            'uses' => function () {
                return 'success';
            },
        ]);

        $response = $this->router->dispatch(Request::create('users/create', 'GET'));

        $this->assertEquals($response->content(), 'success');
    }

    /**
     * @expectedException \Illuminate\Auth\Access\AuthorizationException
     * @expectedExceptionMessage This action is unauthorized.
     */
    public function testModelUnauthorized()
    {
        $post = new stdClass;

        $this->router->bind('post', function () use ($post) {
            return $post;
        });

        $this->gate()->define('edit', function ($user, $model) use ($post) {
            $this->assertSame($model, $post);

            return false;
        });

        $this->router->get('posts/{post}/edit', [
            'middleware' => [SubstituteBindings::class, Authorize::class.':edit,post'],
            'uses' => function () {
                return 'success';
            },
        ]);

        $this->router->dispatch(Request::create('posts/1/edit', 'GET'));
    }

    public function testModelAuthorized()
    {
        $post = new stdClass;

        $this->router->bind('post', function () use ($post) {
            return $post;
        });

        $this->gate()->define('edit', function ($user, $model) use ($post) {
            $this->assertSame($model, $post);

            return true;
        });

        $this->router->get('posts/{post}/edit', [
            'middleware' => [SubstituteBindings::class, Authorize::class.':edit,post'],
            'uses' => function () {
                return 'success';
            },
        ]);

        $response = $this->router->dispatch(Request::create('posts/1/edit', 'GET'));

        $this->assertEquals($response->content(), 'success');
    }

    public function testModelInstanceAsParameter()
    {
        $instance = m::mock(\Illuminate\Database\Eloquent\Model::class);

        $this->gate()->define('success', function ($user, $model) use ($instance) {
            $this->assertSame($model, $instance);

            return true;
        });

        $request = m::mock(Request::class);

        $nextParam = null;

        $next = function ($param) use (&$nextParam) {
            $nextParam = $param;
        };

        (new Authorize($this->container->make(Auth::class), $this->gate()))
            ->handle($request, $next, 'success', $instance);
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
