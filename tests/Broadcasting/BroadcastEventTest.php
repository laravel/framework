<?php

namespace Illuminate\Tests\Broadcasting;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Contracts\Broadcasting\Broadcaster;

class BroadcastEventTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testBasicEventBroadcastParameterFormatting()
    {
        $broadcaster = m::mock(Broadcaster::class);

        $broadcaster->shouldReceive('broadcast')->once()->with(
            ['test-channel'], TestBroadcastEvent::class, ['firstName' => 'Taylor', 'lastName' => 'Otwell', 'collection' => ['foo' => 'bar']]
        );

        $event = new TestBroadcastEvent;

        (new \Illuminate\Broadcasting\BroadcastEvent($event))->handle($broadcaster);
    }

    public function testManualParameterSpecification()
    {
        $broadcaster = m::mock(Broadcaster::class);

        $broadcaster->shouldReceive('broadcast')->once()->with(
            ['test-channel'], TestBroadcastEventWithManualData::class, ['name' => 'Taylor', 'socket' => null]
        );

        $event = new TestBroadcastEventWithManualData;

        (new BroadcastEvent($event))->handle($broadcaster);
    }

    public function testBroadcastWithHandlesEloquentResource()
    {
        app()->instance('request', m::mock(Request::class));
        $broadcaster = m::mock(Broadcaster::class);

        $broadcaster->shouldReceive('broadcast')->once()->with(
            ['test-channel'], TestBroadcastEventWithEloquentResource::class, ['id' => 1, 'name' => 'Mohamed', 'socket' => null]
        );

        $event = new TestBroadcastEventWithEloquentResource;

        (new BroadcastEvent($event))->handle($broadcaster);
    }
}

class TestBroadcastEvent
{
    public $firstName = 'Taylor';
    public $lastName = 'Otwell';
    public $collection;
    private $title = 'Developer';

    public function __construct()
    {
        $this->collection = collect(['foo' => 'bar']);
    }

    public function broadcastOn()
    {
        return ['test-channel'];
    }
}

class TestBroadcastEventWithManualData extends TestBroadcastEvent
{
    public function broadcastWith()
    {
        return ['name' => 'Taylor'];
    }
}

class TestBroadcastEventWithEloquentResource extends TestBroadcastEvent
{
    public function broadcastWith()
    {
        return new TestEloquentResource(new TestUser);
    }
}

class TestUser
{
    public $id = 1;
    public $name = 'Mohamed';
}

class TestEloquentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
