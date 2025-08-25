<?php

namespace Illuminate\Tests\Broadcasting;

use Exception;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Container\Container;
use Illuminate\Contracts\Routing\BindingRegistrar;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\RouteBinding;
use Mockery as m;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BroadcasterTest extends TestCase
{
    /**
     * @var \Illuminate\Tests\Broadcasting\FakeBroadcaster
     */
    public $broadcaster;

    protected function setUp(): void
    {
        parent::setUp();

        $this->broadcaster = new FakeBroadcaster;
    }

    protected function tearDown(): void
    {
        m::close();

        Container::setInstance(null);
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

        // Test Explicit Binding...
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

    public function testModelRouteBinding()
    {
        $container = new Container;
        Container::setInstance($container);
        $binder = m::mock(BindingRegistrar::class);
        $callback = RouteBinding::forModel($container, BroadcasterTestEloquentModelStub::class);

        $binder->shouldReceive('getBindingCallback')->times(2)->with('model')->andReturn($callback);
        $container->instance(BindingRegistrar::class, $binder);
        $callback = function ($user, $model) {
            //
        };
        $parameters = $this->broadcaster->extractAuthParameters('something.{model}', 'something.1', $callback);
        $this->assertEquals(['model.1.instance'], $parameters);
        Container::setInstance(new Container);
    }

    public function testUnknownChannelAuthHandlerTypeThrowsException()
    {
        $this->expectException(Exception::class);

        $this->broadcaster->extractAuthParameters('asd.{model}.{nonModel}', 'asd.1.something', 123);
    }

    public function testCanRegisterChannelsAsClasses()
    {
        $this->broadcaster->channel('something', function () {
            //
        });

        $this->broadcaster->channel('somethingelse', DummyBroadcastingChannel::class);
    }

    public function testNotFoundThrowsHttpException()
    {
        $this->expectException(HttpException::class);

        $callback = function ($user, BroadcasterTestEloquentModelNotFoundStub $model) {
            //
        };
        $this->broadcaster->extractAuthParameters('asd.{model}', 'asd.1', $callback);
    }

    public function testCanRegisterChannelsWithoutOptions()
    {
        $this->broadcaster->channel('somechannel', function () {
            //
        });
    }

    public function testCanRegisterChannelsWithOptions()
    {
        $options = ['a' => ['b', 'c']];
        $this->broadcaster->channel('somechannel', function () {
            //
        }, $options);
    }

    public function testCanRetrieveChannelsOptions()
    {
        $options = ['a' => ['b', 'c']];
        $this->broadcaster->channel('somechannel', function () {
            //
        }, $options);

        $this->assertEquals(
            $options,
            $this->broadcaster->retrieveChannelOptions('somechannel')
        );
    }

    public function testCanRetrieveChannelsOptionsUsingAChannelNameContainingArgs()
    {
        $options = ['a' => ['b', 'c']];
        $this->broadcaster->channel('somechannel.{id}.test.{text}', function () {
            //
        }, $options);

        $this->assertEquals(
            $options,
            $this->broadcaster->retrieveChannelOptions('somechannel.23.test.mytext')
        );
    }

    public function testCanRetrieveChannelsOptionsWhenMultipleChannelsAreRegistered()
    {
        $options = ['a' => ['b', 'c']];
        $this->broadcaster->channel('somechannel', function () {
            //
        });
        $this->broadcaster->channel('someotherchannel', function () {
            //
        }, $options);

        $this->assertEquals(
            $options,
            $this->broadcaster->retrieveChannelOptions('someotherchannel')
        );
    }

    public function testDontRetrieveChannelsOptionsWhenChannelDoesntExists()
    {
        $options = ['a' => ['b', 'c']];
        $this->broadcaster->channel('somechannel', function () {
            //
        }, $options);

        $this->assertEquals(
            [],
            $this->broadcaster->retrieveChannelOptions('someotherchannel')
        );
    }

    public function testRetrieveUserWithoutGuard()
    {
        $this->broadcaster->channel('somechannel', function () {
            //
        });

        $request = m::mock(Request::class);
        $request->shouldReceive('user')
            ->once()
            ->withNoArgs()
            ->andReturn(new DummyUser);

        $this->assertInstanceOf(
            DummyUser::class,
            $this->broadcaster->retrieveUser($request, 'somechannel')
        );
    }

    public function testRetrieveUserWithOneGuardUsingAStringForSpecifyingGuard()
    {
        $this->broadcaster->channel('somechannel', function () {
            //
        }, ['guards' => 'myguard']);

        $request = m::mock(Request::class);
        $request->shouldReceive('user')
            ->once()
            ->with('myguard')
            ->andReturn(new DummyUser);

        $this->assertInstanceOf(
            DummyUser::class,
            $this->broadcaster->retrieveUser($request, 'somechannel')
        );
    }

    public function testRetrieveUserWithMultipleGuardsAndRespectGuardsOrder()
    {
        $this->broadcaster->channel('somechannel', function () {
            //
        }, ['guards' => ['myguard1', 'myguard2']]);
        $this->broadcaster->channel('someotherchannel', function () {
            //
        }, ['guards' => ['myguard2', 'myguard1']]);

        $request = m::mock(Request::class);
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
            $this->broadcaster->retrieveUser($request, 'somechannel')
        );

        $this->assertInstanceOf(
            DummyUser::class,
            $this->broadcaster->retrieveUser($request, 'someotherchannel')
        );
    }

    public function testRetrieveUserDontUseDefaultGuardWhenOneGuardSpecified()
    {
        $this->broadcaster->channel('somechannel', function () {
            //
        }, ['guards' => 'myguard']);

        $request = m::mock(Request::class);
        $request->shouldReceive('user')
            ->once()
            ->with('myguard')
            ->andReturn(null);
        $request->shouldNotReceive('user')
            ->withNoArgs();

        $this->broadcaster->retrieveUser($request, 'somechannel');
    }

    public function testRetrieveUserDontUseDefaultGuardWhenMultipleGuardsSpecified()
    {
        $this->broadcaster->channel('somechannel', function () {
            //
        }, ['guards' => ['myguard1', 'myguard2']]);

        $request = m::mock(Request::class);
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

        $this->broadcaster->retrieveUser($request, 'somechannel');
    }

    public function testUserAuthenticationWithValidUser()
    {
        $this->broadcaster->resolveAuthenticatedUserUsing(function ($request) {
            return ['id' => '12345', 'socket' => $request->socket_id];
        });

        $user = $this->broadcaster->resolveAuthenticatedUser(new Request(['socket_id' => '1234.1234']));

        $this->assertSame([
            'id' => '12345',
            'socket' => '1234.1234',
        ], $user);
    }

    public function testUserAuthenticationWithInvalidUser()
    {
        $this->broadcaster->resolveAuthenticatedUserUsing(function ($request) {
            return null;
        });

        $user = $this->broadcaster->resolveAuthenticatedUser(new Request(['socket_id' => '1234.1234']));

        $this->assertNull($user);
    }

    public function testUserAuthenticationWithoutResolve()
    {
        $this->assertNull($this->broadcaster->resolveAuthenticatedUser(new Request(['socket_id' => '1234.1234'])));
    }

    #[DataProvider('channelNameMatchPatternProvider')]
    public function testChannelNameMatchPattern($channel, $pattern, $shouldMatch)
    {
        $this->assertEquals($shouldMatch, $this->broadcaster->channelNameMatchesPattern($channel, $pattern));
    }

    public static function channelNameMatchPatternProvider()
    {
        return [
            ['something', 'something', true],
            ['something.23', 'something.{id}', true],
            ['something.23.test', 'something.{id}.test', true],
            ['something.23.test.42', 'something.{id}.test.{id2}', true],
            ['something-23:test-42', 'something-{id}:test-{id2}', true],
            ['something..test.42', 'something.{id}.test.{id2}', false],
            ['23:string:test', '{id}:string:{text}', true],
            ['something.23', 'something', false],
            ['something.23.test.42', 'something.test.{id}', false],
            ['something-23-test-42', 'something-{id}-test', false],
            ['23:test', '{id}:test:abcd', false],
            ['customer.order.1', 'order.{id}', false],
            ['customerorder.1', 'order.{id}', false],
        ];
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

    public function retrieveChannelOptions($channel)
    {
        return parent::retrieveChannelOptions($channel);
    }

    public function retrieveUser($request, $channel)
    {
        return parent::retrieveUser($request, $channel);
    }

    public function channelNameMatchesPattern($channel, $pattern)
    {
        return parent::channelNameMatchesPattern($channel, $pattern);
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
    //
}
