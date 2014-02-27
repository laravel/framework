<?php

use Mockery as m;

class QueueIronQueueTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testPushProperlyPushesJobOntoIron()
	{
		$queue = new Illuminate\Queue\IronQueue($iron = m::mock('IronMQ'), $crypt = m::mock('Illuminate\Encryption\Encrypter'), m::mock('Illuminate\Http\Request'), 'default', true);
		$crypt->shouldReceive('encrypt')->once()->with(json_encode(array('job' => 'foo', 'data' => array(1, 2, 3), 'attempts' => 1, 'queue' => 'default')))->andReturn('encrypted');
		$iron->shouldReceive('postMessage')->once()->with('default', 'encrypted', array())->andReturn((object) array('id' => 1));
		$queue->push('foo', array(1, 2, 3));
	}


	public function testPushProperlyPushesJobOntoIronWithoutEncryption()
	{
		$queue = new Illuminate\Queue\IronQueue($iron = m::mock('IronMQ'), $crypt = m::mock('Illuminate\Encryption\Encrypter'), m::mock('Illuminate\Http\Request'), 'default');
		$crypt->shouldReceive('encrypt')->never();
		$iron->shouldReceive('postMessage')->once()->with('default', json_encode(['job' => 'foo', 'data' => [1, 2, 3], 'attempts' => 1, 'queue' => 'default']), array())->andReturn((object) array('id' => 1));
		$queue->push('foo', array(1, 2, 3));
	}


	public function testPushProperlyPushesJobOntoIronWithClosures()
	{
		$queue = new Illuminate\Queue\IronQueue($iron = m::mock('IronMQ'), $crypt = m::mock('Illuminate\Encryption\Encrypter'), m::mock('Illuminate\Http\Request'), 'default', true);
		$name = 'Foo';
		$closure = new Illuminate\Support\SerializableClosure($innerClosure = function() use ($name) { return $name; });
		$crypt->shouldReceive('encrypt')->once()->with(json_encode(array(
			'job' => 'IlluminateQueueClosure', 'data' => array('closure' => serialize($closure)), 'attempts' => 1, 'queue' => 'default'
		)))->andReturn('encrypted');
		$iron->shouldReceive('postMessage')->once()->with('default', 'encrypted', array())->andReturn((object) array('id' => 1));
		$queue->push($innerClosure);
	}


	public function testDelayedPushProperlyPushesJobOntoIron()
	{
		$queue = new Illuminate\Queue\IronQueue($iron = m::mock('IronMQ'), $crypt = m::mock('Illuminate\Encryption\Encrypter'), m::mock('Illuminate\Http\Request'), 'default', true);
		$crypt->shouldReceive('encrypt')->once()->with(json_encode(array(
			'job' => 'foo', 'data' => array(1, 2, 3), 'attempts' => 1, 'queue' => 'default',
		)))->andReturn('encrypted');
		$iron->shouldReceive('postMessage')->once()->with('default', 'encrypted', array('delay' => 5))->andReturn((object) array('id' => 1));
		$queue->later(5, 'foo', array(1, 2, 3));
	}


	public function testDelayedPushProperlyPushesJobOntoIronWithTimestamp()
	{
		$now = Carbon\Carbon::now();
		$queue = $this->getMock('Illuminate\Queue\IronQueue', array('getTime'), array($iron = m::mock('IronMQ'), $crypt = m::mock('Illuminate\Encryption\Encrypter'), m::mock('Illuminate\Http\Request'), 'default', true));
		$queue->expects($this->once())->method('getTime')->will($this->returnValue($now->getTimestamp()));
		$crypt->shouldReceive('encrypt')->once()->with(json_encode(array('job' => 'foo', 'data' => array(1, 2, 3), 'attempts' => 1, 'queue' => 'default')))->andReturn('encrypted');
		$iron->shouldReceive('postMessage')->once()->with('default', 'encrypted', array('delay' => 5))->andReturn((object) array('id' => 1));
		$queue->later($now->addSeconds(5), 'foo', array(1, 2, 3));
	}


	public function testPopProperlyPopsJobOffOfIron()
	{
		$queue = new Illuminate\Queue\IronQueue($iron = m::mock('IronMQ'), $crypt = m::mock('Illuminate\Encryption\Encrypter'), m::mock('Illuminate\Http\Request'), 'default', true);
		$queue->setContainer(m::mock('Illuminate\Container\Container'));
		$iron->shouldReceive('getMessage')->once()->with('default')->andReturn($job = m::mock('IronMQ_Message'));
		$job->body = 'foo';
		$crypt->shouldReceive('decrypt')->once()->with('foo')->andReturn('foo');
		$result = $queue->pop();

		$this->assertInstanceOf('Illuminate\Queue\Jobs\IronJob', $result);
	}


	public function testPopProperlyPopsJobOffOfIronWithoutEncryption()
	{
		$queue = new Illuminate\Queue\IronQueue($iron = m::mock('IronMQ'), $crypt = m::mock('Illuminate\Encryption\Encrypter'), m::mock('Illuminate\Http\Request'), 'default');
		$queue->setContainer(m::mock('Illuminate\Container\Container'));
		$iron->shouldReceive('getMessage')->once()->with('default')->andReturn($job = m::mock('IronMQ_Message'));
		$job->body = 'foo';
		$crypt->shouldReceive('decrypt')->never();
		$result = $queue->pop();

		$this->assertInstanceOf('Illuminate\Queue\Jobs\IronJob', $result);
	}


	public function testPushedJobsCanBeMarshaled()
	{
		$queue = $this->getMock('Illuminate\Queue\IronQueue', array('createPushedIronJob'), array($iron = m::mock('IronMQ'), $crypt = m::mock('Illuminate\Encryption\Encrypter'), $request = m::mock('Illuminate\Http\Request'), 'default', true));
		$request->shouldReceive('header')->once()->with('iron-message-id')->andReturn('message-id');
		$request->shouldReceive('getContent')->once()->andReturn($content = json_encode(array('foo' => 'bar')));
		$crypt->shouldReceive('decrypt')->once()->with($content)->andReturn($content);
		$job = (object) array('id' => 'message-id', 'body' => json_encode(array('foo' => 'bar')), 'pushed' => true);
		$queue->expects($this->once())->method('createPushedIronJob')->with($this->equalTo($job))->will($this->returnValue($mockIronJob = m::mock('StdClass')));
		$mockIronJob->shouldReceive('fire')->once();

		$response = $queue->marshal();

		$this->assertInstanceOf('Illuminate\Http\Response', $response);
		$this->assertEquals(200, $response->getStatusCode());
	}

}
