<?php

use Mockery as m;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;

class BroadcasterTest extends PHPUnit_Framework_TestCase
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
        $parameters = $broadcaster->extractAuthParameters('asd.{model}.{model}.{nonModel}', 'asd.1.uid.something', $callback);
        $this->assertEquals(['model.1.instance', 'model.uid.instance', 'something'], $parameters);

        $callback = function ($user) {
        };
        $parameters = $broadcaster->extractAuthParameters('asd', 'asd', $callback);
        $this->assertEquals([], $parameters);

        $callback = function ($user, $something) {
        };
        $parameters = $broadcaster->extractAuthParameters('asd', 'asd', $callback);
        $this->assertEquals([null], $parameters);

        $callback = function ($user, BroadcasterTestEloquentModelNotFoundStub $model) {
        };
        $parameters = $broadcaster->extractAuthParameters('asd.{model}', 'asd.1', $callback);
        $this->assertEquals([null], $parameters);
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
    }
}
