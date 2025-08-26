<?php

namespace Illuminate\Tests\Routing;

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
use ReflectionMethod;
use stdClass;

class AuthorizeAttributeIntegrationTest extends TestCase
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

    public function testAttributeWorksWithClassBasedModelSpecification()
    {
        $this->gate->define('update-post', function ($user, $post) {
            return $user->id === $post->user_id;
        });

        $post = new TestPostModel;
        $post->user_id = 1;

        $route = $this->createRoute(['post' => $post]);
        $controller = new TestControllerWithClassBasedModels;

        $result = $this->dispatcher->dispatch($route, $controller, 'update');

        $this->assertEquals('updated with class', $result);
    }

    public function testAttributeHandlesNonModelParameters()
    {
        $this->gate->define('view-dashboard', function ($user) {
            return $user->id === 1;
        });

        $route = $this->createRoute(['id' => 123, 'name' => 'test']);
        $controller = new TestControllerWithNonModelParams;

        $result = $this->dispatcher->dispatch($route, $controller, 'show');

        $this->assertEquals('dashboard shown', $result);
    }

    public function testAttributeWorksWithEnumAbilities()
    {
        $this->gate->define('post.update', function ($user, $post) {
            return $user->id === $post->user_id;
        });

        $post = new TestPostModel;
        $post->user_id = 1;

        $route = $this->createRoute(['post' => $post]);
        $controller = new TestControllerWithEnumAbility;

        $result = $this->dispatcher->dispatch($route, $controller, 'update');

        $this->assertEquals('updated with enum', $result);
    }

    public function testMultipleAuthorizeAttributesOnSameMethod()
    {
        $reflection = new ReflectionMethod(TestControllerWithMultipleAttributes::class, 'update');
        $attributes = $reflection->getAttributes(Authorize::class);

        // Should only process the first attribute
        $this->assertCount(2, $attributes);

        $this->gate->define('first-ability', function ($user) {
            return true;
        });

        $this->gate->define('second-ability', function ($user) {
            return false;
        });

        $route = $this->createRoute();
        $controller = new TestControllerWithMultipleAttributes;

        // Should pass because only the first attribute is processed
        $result = $this->dispatcher->dispatch($route, $controller, 'update');

        $this->assertEquals('updated with multiple', $result);
    }

    public function testAttributeWithMissingModelParameter()
    {
        $this->gate->define('update-post', function ($user, $post = null) {
            return $user->id === 1 && $post === null;
        });

        $route = $this->createRoute(); // No post parameter
        $controller = new TestControllerWithMissingParam;

        $result = $this->dispatcher->dispatch($route, $controller, 'update');

        $this->assertEquals('updated without param', $result);
    }

    public function testAttributeFailsWhenGateThrowsException()
    {
        $this->gate->define('update-post', function ($user, $post) {
            throw new \Exception('Custom gate error');
        });

        $post = new TestPostModel;
        $post->user_id = 1;

        $route = $this->createRoute(['post' => $post]);
        $controller = new TestControllerWithClassBasedModels;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Custom gate error');

        $this->dispatcher->dispatch($route, $controller, 'update');
    }

    public function testAttributeWorksWithControllerMethodsWithoutParameters()
    {
        $this->gate->define('list-posts', function ($user) {
            return $user->id === 1;
        });

        $route = $this->createRoute();
        $controller = new TestControllerWithNoParams;

        $result = $this->dispatcher->dispatch($route, $controller, 'index');

        $this->assertEquals('listed', $result);
    }

    protected function createRoute($parameters = [])
    {
        $route = m::mock(Route::class);
        $route->shouldReceive('parametersWithoutNulls')->andReturn($parameters);

        return $route;
    }
}

class TestPostModel extends Model
{
    public $id;
    public $user_id;
}

class TestControllerWithClassBasedModels extends Controller
{
    #[Authorize('update-post', TestPostModel::class)]
    public function update(TestPostModel $post)
    {
        return 'updated with class';
    }
}

class TestControllerWithNonModelParams extends Controller
{
    #[Authorize('view-dashboard')]
    public function show(int $id, string $name)
    {
        return 'dashboard shown';
    }
}

class TestControllerWithEnumAbility extends Controller
{
    #[Authorize('post.update')]
    public function update(TestPostModel $post)
    {
        return 'updated with enum';
    }
}

class TestControllerWithMultipleAttributes extends Controller
{
    #[Authorize('first-ability')]
    #[Authorize('second-ability')]
    public function update()
    {
        return 'updated with multiple';
    }
}

class TestControllerWithMissingParam extends Controller
{
    #[Authorize('update-post')]
    public function update(?TestPostModel $post = null)
    {
        return 'updated without param';
    }
}

class TestControllerWithNoParams extends Controller
{
    #[Authorize('list-posts')]
    public function index()
    {
        return 'listed';
    }
}
