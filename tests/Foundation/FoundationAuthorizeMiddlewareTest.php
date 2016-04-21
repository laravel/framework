<?php

use Mockery as m;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Auth\Access\Gate;
use Illuminate\Events\Dispatcher;
use Illuminate\Routing\Redirector;
use Illuminate\Container\Container;
use Illuminate\Routing\ResponseFactory;
use Illuminate\View\Factory as ViewFactory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\Middleware\Authorize;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Contracts\Routing\ResponseFactory as ResponseFactoryContract;

class FoundationAuthorizeMiddlewareTest extends PHPUnit_Framework_TestCase
{
    protected $container;
    protected $user;

    public function setUp()
    {
        parent::setUp();

        $this->user = new stdClass;

        $this->container = Container::setInstance(new Container);

        $this->container->singleton(GateContract::class, function () {
            return new Gate($this->container, function () {
                return $this->user;
            });
        });

        $this->router = new Router(new Dispatcher, $this->container);
    }

    public function tearDown()
    {
        m::close();
    }

    public function testUnauthenticated()
    {
        $this->user = null;
        $this->container->alias(Redirector::class, 'redirect');
        $this->container->bind(ResponseFactoryContract::class, function () {
            return new ResponseFactory(m::mock(ViewFactory::class), m::mock(Redirector::class));
        });

        $this->router->get('dashboard', [
            'middleware' => Authorize::class.':access-dashboard',
            'uses' => function () { return 'success'; },
        ]);

        $response = $this->router->dispatch($this->request('dashboard', 'GET', true));

        $this->assertEquals($response->getStatusCode(), 401);
    }

    public function testSimpleAbilityUnauthorized()
    {
        $this->setExpectedException(AuthorizationException::class);

        $this->gate()->define('access-dashboard', function ($user, $additional = null) {
            $this->assertNull($additional);

            return false;
        });

        $this->router->get('dashboard', [
            'middleware' => Authorize::class.':access-dashboard',
            'uses' => function () { return 'success'; },
        ]);

        $this->router->dispatch($this->request('dashboard', 'GET'));
    }

    public function testSimpleAbilityAuthorized()
    {
        $this->gate()->define('access-dashboard', function ($user) {
            return true;
        });

        $this->router->get('dashboard', [
            'middleware' => Authorize::class.':access-dashboard',
            'uses' => function () { return 'success'; },
        ]);

        $response = $this->router->dispatch($this->request('dashboard', 'GET'));

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

        $this->router->dispatch($this->request('users/create', 'GET'));
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

        $response = $this->router->dispatch($this->request('users/create', 'GET'));

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

        $this->router->dispatch($this->request('posts/1/edit', 'GET'));
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

        $response = $this->router->dispatch($this->request('posts/1/edit', 'GET'));

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

    /**
     * Create an instance of the request class.
     *
     * @param  string  $url
     * @param  string  $method
     * @param  bool  $ajax
     * @return \Illuminate\Http\Request
     */
    protected function request($url, $method, $ajax = false)
    {
        $request = Request::create($url, $method)->setUserResolver(function () {
            return $this->user;
        });

        if ($ajax) {
            $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        }

        return $request;
    }
}
