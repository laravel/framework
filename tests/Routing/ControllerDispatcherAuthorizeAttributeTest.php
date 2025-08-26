<?php

namespace Illuminate\Tests\Routing;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Access\Gate;
use Illuminate\Auth\Attributes\Authorize;
use Illuminate\Container\Container;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Controller;
use Illuminate\Routing\ControllerDispatcher;
use Illuminate\Routing\Route;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use stdClass;

class ControllerDispatcherAuthorizeAttributeTest extends TestCase
{
    protected $container;
    protected $user;
    protected $gate;
    protected $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = new stdClass;
        $this->user->id = 1;

        $this->container = new Container;
        Container::setInstance($this->container);

        $this->gate = new Gate($this->container, function () {
            return $this->user;
        });

        $this->container->instance(GateContract::class, $this->gate);
        $this->dispatcher = new ControllerDispatcher($this->container);
    }

    protected function tearDown(): void
    {
        m::close();
        Container::setInstance(null);
    }

    public function testAuthorizeAttributeWithParamsIsHandledForControllers()
    {
        $this->gate->define('update-post', function ($user, $post) {
            return $user->id === $post->user_id;
        });

        $post = new TestPost;
        $post->user_id = 1;

        $route = $this->createRoute(['post' => $post]);
        $controller = new TestControllerWithAttribute;

        $result = $this->dispatcher->dispatch($route, $controller, 'update');

        $this->assertEquals('updated', $result);
    }

    public function testAuthorizeAttributeWithoutParamsIsHandledForController()
    {
        $this->gate->define('create-post', function ($user) {
            return $user->id === 1;
        });

        $route = $this->createRoute();
        $controller = new TestControllerWithAttribute;

        $result = $this->dispatcher->dispatch($route, $controller, 'create');

        $this->assertEquals('created', $result);
    }

    public function testAuthorizeAttributeThrowsAuthorizationExceptionWhenUnauthorized()
    {
        $this->gate->define('update-post', function ($user, $post) {
            return $user->id === $post->user_id;
        });

        $post = new TestPost;
        $post->user_id = 2;

        $route = $this->createRoute(['post' => $post]);
        $controller = new TestControllerWithAttribute;

        $this->expectException(AuthorizationException::class);

        $this->dispatcher->dispatch($route, $controller, 'update');
    }

    public function testAuthorizeAttributeAutoResolvesModelsFromMethodParameters()
    {
        $this->gate->define('update-post', function ($user, $post) {
            return $user->id === $post->user_id;
        });

        $post = new TestPost;
        $post->user_id = 1;

        $route = $this->createRoute(['post' => $post]);
        $controller = new TestControllerWithAutoResolve;

        $result = $this->dispatcher->dispatch($route, $controller, 'update');

        $this->assertEquals('updated', $result);
    }

    public function testAuthorizeAttributeWorksWithMultipleModels()
    {
        $this->gate->define('edit-post-comment', function ($user, $post, $comment) {
            return $user->id === $post->user_id && $comment->post_id === $post->id;
        });

        $post = new TestPost;
        $post->id = 1;
        $post->user_id = 1;

        $comment = new TestComment;
        $comment->post_id = 1;

        $route = $this->createRoute(['post' => $post, 'comment' => $comment]);
        $controller = new TestControllerWithMultipleModels;

        $result = $this->dispatcher->dispatch($route, $controller, 'updateComment');

        $this->assertEquals('comment updated', $result);
    }

    public function testAuthorizeAttributeWorksWithSpecificModelParameters()
    {
        $this->gate->define('update-post', function ($user, $post) {
            return $user->id === $post->user_id;
        });

        $post = new TestPost;
        $post->user_id = 1;

        $user = new TestUser;
        $user->id = 2;

        $route = $this->createRoute(['post' => $post, 'user' => $user]);
        $controller = new TestControllerWithSpecificModels;

        $result = $this->dispatcher->dispatch($route, $controller, 'update');

        $this->assertEquals('updated', $result);
    }

    public function testControllerMethodWithoutAuthorizeAttributeIsNotAffected()
    {
        $route = $this->createRoute();
        $controller = new TestControllerWithAttribute;

        $result = $this->dispatcher->dispatch($route, $controller, 'index');

        $this->assertEquals('index', $result);
    }

    protected function createRoute($parameters = [])
    {
        $route = m::mock(Route::class);
        $route->shouldReceive('parametersWithoutNulls')->andReturn($parameters);

        return $route;
    }
}

class TestPost extends Model
{
    public $id;
    public $user_id;
}

class TestComment extends Model
{
    public $post_id;
}

class TestUser extends Model
{
    public $id;
}

class TestControllerWithAttribute extends Controller
{
    #[Authorize('create-post')]
    public function create()
    {
        return 'created';
    }

    #[Authorize('update-post', 'post')]
    public function update(TestPost $post)
    {
        return 'updated';
    }

    public function index()
    {
        return 'index';
    }
}

class TestControllerWithAutoResolve extends Controller
{
    #[Authorize('update-post')]
    public function update(TestPost $post)
    {
        return 'updated';
    }
}

class TestControllerWithMultipleModels extends Controller
{
    #[Authorize('edit-post-comment')]
    public function updateComment(TestPost $post, TestComment $comment)
    {
        return 'comment updated';
    }
}

class TestControllerWithSpecificModels extends Controller
{
    #[Authorize('update-post', 'post')]
    public function update(TestPost $post, TestUser $user)
    {
        return 'updated';
    }
}
