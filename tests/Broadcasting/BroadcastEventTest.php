<?php

use Mockery as m;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Contracts\Broadcasting\Broadcaster;

class BroadcastEventTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testBasicEventBroadcastParameterFormatting()
	{
		$broadcaster = m::mock(Broadcaster::class);

		$broadcaster->shouldReceive('broadcast')->once()->with(
			['test-channel'], 'TestBroadcastEvent', ['firstName' => 'Taylor', 'lastName' => 'Otwell', 'collection' => ['foo' => 'bar']]
		);

		$event = new TestBroadcastEvent;
		$serializedEvent = serialize($event);
		$jobData = ['event' => $serializedEvent];

		$job = m::mock(Job::class);
		$job->shouldReceive('delete')->once();

		(new BroadcastEvent($broadcaster))->fire($job, $jobData);
	}


	public function testManualParameterSpecification()
	{
		$broadcaster = m::mock(Broadcaster::class);

		$broadcaster->shouldReceive('broadcast')->once()->with(
			['test-channel'], 'TestBroadcastEventWithManualData', ['name' => 'Taylor']
		);

		$event = new TestBroadcastEventWithManualData;
		$serializedEvent = serialize($event);
		$jobData = ['event' => $serializedEvent];

		$job = m::mock(Job::class);
		$job->shouldReceive('delete')->once();

		(new BroadcastEvent($broadcaster))->fire($job, $jobData);
	}

}


class TestBroadcastEvent {
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


class TestBroadcastEventWithManualData extends TestBroadcastEvent {
	public function broadcastWith()
	{
		return ['name' => 'Taylor'];
	}
}

