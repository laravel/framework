<?php

namespace Illuminate\Tests\Routing;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Routing\ImplicitRouteBinding;
use Illuminate\Routing\Route;
use PHPUnit\Framework\TestCase;

class ImplicitRouteBindingTest extends TestCase
{
    public function test_it_can_resolve_the_implicit_route_bindings_for_the_given_route()
    {
        $this->expectNotToPerformAssertions();

        $action = ['uses' => function (ImplicitRouteBindingUser $user) {
            return $user;
        }];

        $route = new Route('GET', '/test', $action);
        $route->parameters = ['user' => new ImplicitRouteBindingUser];

        $route->prepareForSerialization();

        $container = Container::getInstance();

        ImplicitRouteBinding::resolveForRoute($container, $route);
    }

    /** @test */
    public function it_enforces_scoping_of_implicit_route_bindings(): void
    {
        $this->prepareEloquent();

        $action = ['uses' => function (ImplicitRouteBindingUser $user, ImplicitRouteBindingPost $post) {
            return $user;
        }];

        $route = new Route('GET', '/users/{user}/posts/{post}', $action);
        $route->action['scoping'] = true;
        $route->parameters = ['user' => 1, 'post' => 1];

        $container = Container::getInstance();

        $this->expectException(ModelNotFoundException::class);
        ImplicitRouteBinding::resolveForRoute($container, $route);
    }

    /** @test */
    public function it_does_not_enforce_scoping_of_implicit_route_bindings(): void
    {
        $this->prepareEloquent();

        $action = ['uses' => function (ImplicitRouteBindingUser $user, ImplicitRouteBindingPost $post) {
            return $user;
        }];

        $route = new Route('GET', '/users/{user}/posts/{post}', $action);
        $route->parameters = ['user' => 1, 'post' => 1];

        $container = Container::getInstance();

        ImplicitRouteBinding::resolveForRoute($container, $route);

        $this->assertTrue($route->parameter('user')->is(ImplicitRouteBindingUser::findOrFail(1)));
        $this->assertTrue($route->parameter('post')->is(ImplicitRouteBindingPost::findOrFail(1)));
    }

    protected function prepareEloquent()
    {
        $db = new DB;
        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $db->bootEloquent();
        $db->setAsGlobal();

        $schema = Eloquent::getConnectionResolver()->connection()->getSchemaBuilder();
        $schema->create('users', function ($table) {
            $table->increments('id');
        });

        $schema->create('posts', function ($table) {
            $table->increments('id');
            $table->integer('user_id');
        });

        ImplicitRouteBindingUser::create(['id' => 1]);
        ImplicitRouteBindingPost::create(['id' => 1, 'user_id' => 2]);
    }
}

class Model extends Eloquent
{
    public $timestamps = false;
    protected $guarded = false;
}

class ImplicitRouteBindingUser extends Model
{
    protected $table = 'users';

    public function posts()
    {
        return $this->hasMany(ImplicitRouteBindingPost::class, 'user_id');
    }
}

class ImplicitRouteBindingPost extends Model
{
    protected $table = 'posts';
}
