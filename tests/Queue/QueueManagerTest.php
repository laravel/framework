<?php

use Mockery as m;
use Illuminate\Queue\QueueManager;

class QueueManagerTest extends TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testDefaultConnectionCanBeResolved()
	{
		$app = array(
			'config' => array(
				'queue.default' => 'sync',
				'queue.connections.sync' => array('driver' => 'sync'),
			),
			'encrypter' => $encrypter = m::mock('Illuminate\Encryption\Encrypter'),
		);

		$manager = new QueueManager($app);
		$connector = m::mock('StdClass');
		$queue = m::mock('StdClass');
		$connector->shouldReceive('connect')->once()->with(array('driver' => 'sync'))->andReturn($queue);
		$manager->addConnector('sync', function() use ($connector) { return $connector; });
		$queue->shouldReceive('setContainer')->once()->with($app);
		$queue->shouldReceive('setEncrypter')->once()->with($encrypter);

		$this->assertSame($queue, $manager->connection('sync'));
	}


	public function testOtherConnectionCanBeResolved()
	{
		$app = array(
			'config' => array(
				'queue.default' => 'sync',
				'queue.connections.foo' => array('driver' => 'bar'),
			),
			'encrypter' => $encrypter = m::mock('Illuminate\Encryption\Encrypter'),
		);

		$manager = new QueueManager($app);
		$connector = m::mock('StdClass');
		$queue = m::mock('StdClass');
		$connector->shouldReceive('connect')->once()->with(array('driver' => 'bar'))->andReturn($queue);
		$manager->addConnector('bar', function() use ($connector) { return $connector; });
		$queue->shouldReceive('setContainer')->once()->with($app);
		$queue->shouldReceive('setEncrypter')->once()->with($encrypter);

		$this->assertSame($queue, $manager->connection('foo'));
	}

}
