<?php

use Mockery as m;

class QueueIronQueueTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testPushProperlyPushesJobOntoIron()
	{
		$queue = new Illuminate\Queue\IronQueue($iron = m::mock('IronMQ'), 'default');
		$iron->shouldReceive('postMessage')->once()->with('default', json_encode(array('job' => 'foo', 'data' => array(1, 2, 3))));
		$queue->push('foo', array(1, 2, 3));
	}


	public function testDelayedPushProperlyPushesJobOntoIron()
	{
		$queue = new Illuminate\Queue\IronQueue($iron = m::mock('IronMQ'), 'default');
		$iron->shouldReceive('postMessage')->once()->with('default', json_encode(array('job' => 'foo', 'data' => array(1, 2, 3))), array('delay' => 5));
		$queue->later(5, 'foo', array(1, 2, 3));
	}


	public function testPopProperlyPopsJobOffOfIron()
	{
		$queue = new Illuminate\Queue\IronQueue($iron = m::mock('IronMQ'), 'default');
		$queue->setContainer(m::mock('Illuminate\Container\Container'));		
		$iron->shouldReceive('getMessage')->once()->with('default')->andReturn($job = m::mock('IronMQ_Message'));
		$result = $queue->pop();

		$this->assertInstanceOf('Illuminate\Queue\Jobs\IronJob', $result);
	}

}