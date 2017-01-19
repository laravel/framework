<?php

namespace Illuminate\Tests\Broadcasting;

use Mockery as m;
use PHPUnit\Framework\TestCase;

class BroadcastEventTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testBasicEventBroadcastParameterFormatting()
    {
        $broadcaster = m::mock('Illuminate\Contracts\Broadcasting\Broadcaster');

        $broadcaster->shouldReceive('broadcast')->once()->with(
            ['test-channel'], 'Illuminate\Tests\Broadcasting\TestBroadcastEvent', ['firstName' => 'Taylor', 'lastName' => 'Otwell', 'collection' => ['foo' => 'bar']]
        );

        $event = new TestBroadcastEvent;

        (new \Illuminate\Broadcasting\BroadcastEvent($event))->handle($broadcaster);
    }

    public function testManualParameterSpecification()
    {
        $broadcaster = m::mock('Illuminate\Contracts\Broadcasting\Broadcaster');

        $broadcaster->shouldReceive('broadcast')->once()->with(
            ['test-channel'], 'Illuminate\Tests\Broadcasting\TestBroadcastEventWithManualData', ['name' => 'Taylor', 'socket' => null]
        );

        $event = new TestBroadcastEventWithManualData;

        (new \Illuminate\Broadcasting\BroadcastEvent($event))->handle($broadcaster);
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
