<?php

namespace Illuminate\Tests\Broadcasting;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Routing\BindingRegistrar;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;

class BroadcasterTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testExtractingParametersWhileCheckingForUserAccess()
    {
        $broadcaster = new FakeBroadcaster();

        $callback = function ($user, BroadcasterTestEloquentModelStub $model, $nonModel) {
        };
        $parameters = $broadcaster->extractAuthParameters('asd.{model}.{nonModel}', 'asd.1.something', $callback);
        $this->assertEquals(['model.1.instance', 'something'], $parameters);

        $callback = function ($user, BroadcasterTestEloquentModelStub $model, BroadcasterTestEloquentModelStub $model2, $something) {
        };
        $parameters = $broadcaster->extractAuthParameters('asd.{model}.{model2}.{nonModel}', 'asd.1.uid.something', $callback);
        $this->assertEquals(['model.1.instance', 'model.uid.instance', 'something'], $parameters);

        $callback = function ($user) {
        };
        $parameters = $broadcaster->extractAuthParameters('asd', 'asd', $callback);
        $this->assertEquals([], $parameters);

        $callback = function ($user, $something) {
        };
        $parameters = $broadcaster->extractAuthParameters('asd', 'asd', $callback);
        $this->assertEquals([], $parameters);

        /*
         * Test Explicit Binding...
         */
        $container = new Container;
        Container::setInstance($container);
        $binder = m::mock(BindingRegistrar::class);
        $binder->shouldReceive('getBindingCallback')->with('model')->andReturn(function () {
            return 'bound';
        });
        $container->instance(BindingRegistrar::class, $binder);
        $callback = function ($user, $model) {
        };
        $parameters = $broadcaster->extractAuthParameters('something.{model}', 'something.1', $callback);
        $this->assertEquals(['bound'], $parameters);
        Container::setInstance(new Container);
    }

    /**
     * @expectedException Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function testNotFoundThrowsHttpException()
    {
        $broadcaster = new FakeBroadcaster();
        $callback = function ($user, BroadcasterTestEloquentModelNotFoundStub $model) {
        };
        $broadcaster->extractAuthParameters('asd.{model}', 'asd.1', $callback);
    }
}

class FakeBroadcaster extends Broadcaster
{
    public function auth($request)
    {
    }

    public function validAuthenticationResponse($request, $result)
    {
    }

    public function broadcast(array $channels, $event, array $payload = [])
    {
    }

    public function extractAuthParameters($pattern, $channel, $callback)
    {
        return parent::extractAuthParameters($pattern, $channel, $callback);
    }
}

class BroadcasterTestEloquentModelStub extends Model
{
    public function getRouteKeyName()
    {
        return 'id';
    }

    public function where($key, $value)
    {
        $this->value = $value;

        return $this;
    }

    public function firstOr()
    {
        return "model.{$this->value}.instance";
    }
}

class BroadcasterTestEloquentModelNotFoundStub extends Model
{
    public function getRouteKeyName()
    {
        return 'id';
    }

    public function where($key, $value)
    {
        $this->value = $value;

        return $this;
    }

    public function firstOr($callback)
    {
        return call_user_func($callback);
    }
}
