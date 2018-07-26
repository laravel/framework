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
        $broadcaster = new FakeBroadcaster;

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
        $binder->shouldReceive('getBindingCallback')->times(2)->with('model')->andReturn(function () {
            return 'bound';
        });
        $container->instance(BindingRegistrar::class, $binder);
        $callback = function ($user, $model) {
        };
        $parameters = $broadcaster->extractAuthParameters('something.{model}', 'something.1', $callback);
        $this->assertEquals(['bound'], $parameters);
        Container::setInstance(new Container);
    }

    public function testCanUseChannelClasses()
    {
        $broadcaster = new FakeBroadcaster;

        $parameters = $broadcaster->extractAuthParameters('asd.{model}.{nonModel}', 'asd.1.something', DummyBroadcastingChannel::class);
        $this->assertEquals(['model.1.instance', 'something'], $parameters);
    }

    /**
     * @expectedException \Exception
     */
    public function testUnknownChannelAuthHandlerTypeThrowsException()
    {
        $broadcaster = new FakeBroadcaster;

        $broadcaster->extractAuthParameters('asd.{model}.{nonModel}', 'asd.1.something', 123);
    }

    public function testCanRegisterChannelsAsClasses()
    {
        $broadcaster = new FakeBroadcaster;

        $broadcaster->channel('something', function () {
        });
        $broadcaster->channel('somethingelse', DummyBroadcastingChannel::class);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function testNotFoundThrowsHttpException()
    {
        $broadcaster = new FakeBroadcaster;
        $callback = function ($user, BroadcasterTestEloquentModelNotFoundStub $model) {
        };
        $broadcaster->extractAuthParameters('asd.{model}', 'asd.1', $callback);
    }

    public function testCanRegisterChannelsWithoutOptions()
    {
        $broadcaster = new FakeBroadcaster;

        $broadcaster->channel('somechannel', function () {});
    }

    public function testCanRegisterChannelsWithOptions()
    {
        $broadcaster = new FakeBroadcaster;

        $options = [ 'a' => [ 'b', 'c' ] ];
        $broadcaster->channel('somechannel', function () {}, $options);

        $this->assertEquals(
            $options,
            $broadcaster->retrieveChannelOptions('somechannel')
        );
    }

    public function testRetrieveUserWithoutGuard()
    {
        $broadcaster = new FakeBroadcaster;

        $broadcaster->channel('somechannel', function () {});

        $request = m::mock(\Illuminate\Http\Request::class);
        $request->shouldReceive('user')
                ->once()
                ->withNoArgs()
                ->andReturn(new DummyUser);

        $this->assertInstanceOf(
            DummyUser::class,
            $broadcaster->retrieveUser($request, 'somechannel')
        );
    }

    public function testRetrieveUserWithOneGuardUsingAStringForSpecifyingGuard()
    {
        $broadcaster = new FakeBroadcaster;

        $broadcaster->channel('somechannel', function () {}, ['guards' => 'myguard']);

        $request = m::mock(\Illuminate\Http\Request::class);
        $request->shouldReceive('user')
                ->once()
                ->with('myguard')
                ->andReturn(new DummyUser);

        $this->assertInstanceOf(
            DummyUser::class,
            $broadcaster->retrieveUser($request, 'somechannel')
        );
    }

    public function testRetrieveUserWithMultipleGuardsAndRespectGuardsOrder()
    {
        $broadcaster = new FakeBroadcaster;

        $broadcaster->channel('somechannel', function () {}, ['guards' => ['myguard1', 'myguard2']]);
        $broadcaster->channel('someotherchannel', function () {}, ['guards' => ['myguard2', 'myguard1']]);


        $request = m::mock(\Illuminate\Http\Request::class);
        $request->shouldReceive('user')
                ->once()
                ->with('myguard1')
                ->andReturn(null);
        $request->shouldReceive('user')
                ->twice()
                ->with('myguard2')
                ->andReturn(new DummyUser)
                ->ordered('user');

        $this->assertInstanceOf(
            DummyUser::class,
            $broadcaster->retrieveUser($request, 'somechannel')
        );

        $this->assertInstanceOf(
            DummyUser::class,
            $broadcaster->retrieveUser($request, 'someotherchannel')
        );
    }

    public function testRetrieveUserDontUseDefaultGuardWhenOneGuardSpecified()
    {
        $broadcaster = new FakeBroadcaster;

        $broadcaster->channel('somechannel', function () {}, ['guards' => 'myguard']);

        $request = m::mock(\Illuminate\Http\Request::class);
        $request->shouldReceive('user')
                ->once()
                ->with('myguard')
                ->andReturn(null);
        $request->shouldNotReceive('user')
                ->withNoArgs();

        $broadcaster->retrieveUser($request, 'somechannel');
    }

    public function testRetrieveUserDontUseDefaultGuardWhenMultipleGuardsSpecified()
    {
        $broadcaster = new FakeBroadcaster;

        $broadcaster->channel('somechannel', function () {}, ['guards' => ['myguard1', 'myguard2']]);


        $request = m::mock(\Illuminate\Http\Request::class);
        $request->shouldReceive('user')
                ->once()
                ->with('myguard1')
                ->andReturn(null);
        $request->shouldReceive('user')
                ->once()
                ->with('myguard2')
                ->andReturn(null);
        $request->shouldNotReceive('user')
                ->withNoArgs();

        $broadcaster->retrieveUser($request, 'somechannel');
    }

    /**
     * @dataProvider channelNameMatchPatternProvider
     */
    public function testChannelNameMatchPattern($channel, $pattern, $shouldMatch)
    {
        $broadcaster = new FakeBroadcaster;

        $this->assertEquals($shouldMatch, $broadcaster->channelNameMatchPattern($channel, $pattern));
    }

    public function channelNameMatchPatternProvider() {
        return [
            ['something', 'something', true],
            ['something.23', 'something.{id}', true],
            ['something.23.test', 'something.{id}.test', true],
            ['something.23.test.42', 'something.{id}.test.{id2}', true],
            ['something-23:test-42', 'something-{id}:test-{id2}', true],
            ['something..test.42', 'something.{id}.test.{id2}', true],
            ['23:string:test', '{id}:string:{text}', true],
            ['something.23', 'something', false],
            ['something.23.test.42', 'something.test.{id}', false],
            ['something-23-test-42', 'something-{id}-test', false],
            ['23:test', '{id}:test:abcd', false],
        ];
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

    public function retrieveChannelOptions($channel)
    {
        return parent::retrieveChannelOptions($channel);
    }

    public function retrieveUser($request, $channel)
    {
        return parent::retrieveUser($request, $channel);
    }

    public function channelNameMatchPattern($channel, $pattern)
    {
        return parent::channelNameMatchPattern($channel, $pattern);
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

class DummyUser
{

}
