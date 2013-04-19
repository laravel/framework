<?php

use Mockery as m;

class QueueIronQueueTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testPushProperlyPushesJobOntoIron()
	{
		$queue = new Illuminate\Queue\IronQueue($iron = m::mock('IronMQ'), m::mock('Illuminate\Http\Request'), 'default');
		$iron->shouldReceive('postMessage')->once()->with('default', json_encode(array('job' => 'foo', 'data' => array(1, 2, 3))));
		$queue->push('foo', array(1, 2, 3));
	}


	public function testDelayedPushProperlyPushesJobOntoIron()
	{
		$queue = new Illuminate\Queue\IronQueue($iron = m::mock('IronMQ'), m::mock('Illuminate\Http\Request'), 'default');
		$iron->shouldReceive('postMessage')->once()->with('default', json_encode(array('job' => 'foo', 'data' => array(1, 2, 3))), array('delay' => 5));
		$queue->later(5, 'foo', array(1, 2, 3));
	}


	public function testPopProperlyPopsJobOffOfIron()
	{
		$queue = new Illuminate\Queue\IronQueue($iron = m::mock('IronMQ'), m::mock('Illuminate\Http\Request'), 'default');
		$queue->setContainer(m::mock('Illuminate\Container\Container'));		
		$iron->shouldReceive('getMessage')->once()->with('default')->andReturn($job = m::mock('IronMQ_Message'));
		$result = $queue->pop();

		$this->assertInstanceOf('Illuminate\Queue\Jobs\IronJob', $result);
	}


	public function testPushedJobsCanBeMarshaled()
	{
		$queue = $this->getMock('Illuminate\Queue\IronQueue', array('createPushedIronJob'), array($iron = m::mock('IronMQ'), $request = m::mock('Illuminate\Http\Request'), 'default'));
		$request->shouldReceive('header')->once()->with('iron-message-id')->andReturn('message-id');
		$request->shouldReceive('getContent')->once()->andReturn(json_encode(array('foo' => 'bar')));
		$job = (object) array('id' => 'message-id', 'body' => json_encode(array('foo' => 'bar')));
		$queue->expects($this->once())->method('createPushedIronJob')->with($this->equalTo($job))->will($this->returnValue($mockIronJob = m::mock('StdClass')));
		$mockIronJob->shouldReceive('fire')->once();

		$response = $queue->marshal();

		$this->assertInstanceOf('Illuminate\Http\Response', $response);
		$this->assertEquals(200, $response->getStatusCode());
	}

}