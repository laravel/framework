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

class AuthorizeAttributePolicyTest extends TestCase
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

    public function testAuthorizeAttributeWorksWithPolicies()
    {
        $this->gate->policy(TestPostWithPolicies::class, TestPostPolicy::class);

        $post = new TestPostWithPolicies;
        $post->user_id = 1;

        $route = $this->createRoute(['post' => $post]);
        $controller = new TestControllerWithPolicy;

        $result = $this->dispatcher->dispatch($route, $controller, 'update');

        $this->assertEquals('updated via policy', $result);
    }

    public function testAuthorizeAttributeWithPolicyDeniesAccess()
    {
        $this->gate->policy(TestPostWithPolicies::class, TestPostPolicy::class);

        $post = new TestPostWithPolicies;
        $post->user_id = 2;

        $route = $this->createRoute(['post' => $post]);
        $controller = new TestControllerWithPolicy;

        $this->expectException(AuthorizationException::class);

        $this->dispatcher->dispatch($route, $controller, 'update');
    }

    public function testAuthorizeAttributeWorksWithPolicyAndMultipleModels()
    {
        $this->gate->policy(TestPostWithPolicies::class, TestPostPolicy::class);
        $this->gate->policy(TestCommentWithPolicies::class, TestCommentPolicy::class);

        $post = new TestPostWithPolicies;
        $post->id = 1;
        $post->user_id = 1;

        $comment = new TestCommentWithPolicies;
        $comment->id = 1;
        $comment->post_id = 1;
        $comment->user_id = 1;

        $route = $this->createRoute(['post' => $post, 'comment' => $comment]);
        $controller = new TestControllerWithMultiplePolicyModels;

        $result = $this->dispatcher->dispatch($route, $controller, 'updateComment');

        $this->assertEquals('comment updated via policy', $result);
    }

    public function testAuthorizeAttributeWithCustomPolicyMethod()
    {
        $this->gate->policy(TestPostWithPolicies::class, TestPostPolicy::class);

        $post = new TestPostWithPolicies;
        $post->user_id = 1;

        $route = $this->createRoute(['post' => $post]);
        $controller = new TestControllerWithCustomPolicyMethod;

        $result = $this->dispatcher->dispatch($route, $controller, 'publish');

        $this->assertEquals('published via policy', $result);
    }

    protected function createRoute($parameters = [])
    {
        $route = m::mock(Route::class);
        $route->shouldReceive('parametersWithoutNulls')->andReturn($parameters);

        return $route;
    }
}

// Test Models
class TestPostWithPolicies extends Model
{
    public $id;
    public $user_id;
}

class TestCommentWithPolicies extends Model
{
    public $id;
    public $post_id;
    public $user_id;
}

class TestPostPolicy
{
    public function update($user, $post)
    {
        return $user->id === $post->user_id;
    }

    public function publish($user, $post)
    {
        return $user->id === $post->user_id;
    }
}

class TestCommentPolicy
{
    public function updateComment($user, $comment, $post)
    {
        return $user->id === $post->user_id && $comment->post_id === $post->id;
    }
}

class TestControllerWithPolicy extends Controller
{
    #[Authorize('update')]
    public function update(TestPostWithPolicies $post)
    {
        return 'updated via policy';
    }
}

class TestControllerWithMultiplePolicyModels extends Controller
{
    #[Authorize('updateComment', 'comment', 'post')]
    public function updateComment(TestPostWithPolicies $post, TestCommentWithPolicies $comment)
    {
        return 'comment updated via policy';
    }
}

class TestControllerWithCustomPolicyMethod extends Controller
{
    #[Authorize('publish')]
    public function publish(TestPostWithPolicies $post)
    {
        return 'published via policy';
    }
}
