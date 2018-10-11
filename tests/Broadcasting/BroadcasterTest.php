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
    /**
     * @var \Illuminate\Tests\Broadcasting\FakeBroadcaster
     */
    public $broadcaster;

    public function setUp()
    {
        parent::setUp();

        $this->broadcaster = new FakeBroadcaster;
    }

    public function tearDown()
    {
        m::close();
    }

    public function testExtractingParametersWhileCheckingForUserAccess()
    {
        $callback = function ($user, BroadcasterTestEloquentModelStub $model, $nonModel) {
            //
        };
        $parameters = $this->broadcaster->extractAuthParameters('asd.{model}.{nonModel}', 'asd.1.something', $callback);
        $this->assertEquals(['model.1.instance', 'something'], $parameters);

        $callback = function ($user, BroadcasterTestEloquentModelStub $model, BroadcasterTestEloquentModelStub $model2, $something) {
            //
        };
        $parameters = $this->broadcaster->extractAuthParameters('asd.{model}.{model2}.{nonModel}', 'asd.1.uid.something', $callback);
        $this->assertEquals(['model.1.instance', 'model.uid.instance', 'something'], $parameters);

        $callback = function ($user) {
            //
        };
        $parameters = $this->broadcaster->extractAuthParameters('asd', 'asd', $callback);
        $this->assertEquals([], $parameters);

        $callback = function ($user, $something) {
            //
        };
        $parameters = $this->broadcaster->extractAuthParameters('asd', 'asd', $callback);
        $this->assertEquals([], $parameters);

        /*
         * Test Explicit Binding...
         */
        $container = new Container;
        Container::setInstance($container);
        $binder = m::mock(BindingRegistrar::class);
        $binder->shouldReceive('getBindingCallback')->times(2)->with('model')->andReturn(function () {
            return 'bound';
        });
        $container->instance(BindingRegistrar::class, $binder);
        $callback = function ($user, $model) {
            //
        };
        $parameters = $this->broadcaster->extractAuthParameters('something.{model}', 'something.1', $callback);
        $this->assertEquals(['bound'], $parameters);
        Container::setInstance(new Container);
    }

    public function testCanUseChannelClasses()
    {
        $parameters = $this->broadcaster->extractAuthParameters('asd.{model}.{nonModel}', 'asd.1.something', DummyBroadcastingChannel::class);
        $this->assertEquals(['model.1.instance', 'something'], $parameters);
    }

    /**
     * @expectedException \Exception
     */
    public function testUnknownChannelAuthHandlerTypeThrowsException()
    {
        $this->broadcaster->extractAuthParameters('asd.{model}.{nonModel}', 'asd.1.something', 123);
    }

    public function testCanRegisterChannelsAsClasses()
    {
        $this->broadcaster->channel('something', function () {
            //
        });
        $this->broadcaster->channel('somethingelse', DummyBroadcastingChannel::class);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function testNotFoundThrowsHttpException()
    {
        $callback = function ($user, BroadcasterTestEloquentModelNotFoundStub $model) {
            //
        };
        $this->broadcaster->extractAuthParameters('asd.{model}', 'asd.1', $callback);
    }
}

class FakeBroadcaster extends Broadcaster
{
    public function auth($request)
    {
        //
    }

    public function validAuthenticationResponse($request, $result)
    {
        //
    }

    public function broadcast(array $channels, $event, array $payload = [])
    {
        //
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

    public function first()
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

    public function first()
    {
        //
    }
}

class DummyBroadcastingChannel
{
    public function join($user, BroadcasterTestEloquentModelStub $model, $nonModel)
    {
        //
    }
}
