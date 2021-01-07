<?php

namespace Illuminate\Tests\Routing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\RouteAction;
use Opis\Closure\SerializableClosure;
use PHPUnit\Framework\TestCase;

class RouteActionTest extends TestCase
{
    public function test_it_can_detect_a_serialized_closure()
    {
        $action = ['uses' => serialize(new SerializableClosure(function (RouteActionUser $user) {
            return $user;
        }))];

        $this->assertTrue(RouteAction::containsSerializedClosure($action));

        $action = ['uses' => 'FooController@index'];

        $this->assertFalse(RouteAction::containsSerializedClosure($action));
    }
}

class RouteActionUser extends Model
{
    //
}
