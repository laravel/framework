<?php

use Mockery as m;
use Illuminate\Queue\QueueManager;

class QueueManagerTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testDefaultConnectionCanBeResolved()
	{
		$app = [
			'config' => [
				'queue.default' => 'sync',
				'queue.connections.sync' => ['driver' => 'sync'],
			],
		];

		$manager = new QueueManager($app);
		$connector = m::mock('StdClass');
		$queue = m::mock('StdClass');
		$connector->shouldReceive('connect')->once()->with(['driver' => 'sync'])->andReturn($queue);
		$manager->addConnector('sync', function() use ($connector) { return $connector; });
		$queue->shouldReceive('setContainer')->once()->with($app);

		$this->assertTrue($queue === $manager->connection('sync'));
	}


	public function testOtherConnectionCanBeResolved()
	{
		$app = [
			'config' => [
				'queue.default' => 'sync',
				'queue.connections.foo' => ['driver' => 'bar'],
			],
		];

		$manager = new QueueManager($app);
		$connector = m::mock('StdClass');
		$queue = m::mock('StdClass');
		$connector->shouldReceive('connect')->once()->with(['driver' => 'bar'])->andReturn($queue);
		$manager->addConnector('bar', function() use ($connector) { return $connector; });
		$queue->shouldReceive('setContainer')->once()->with($app);

		$this->assertTrue($queue === $manager->connection('foo'));
	}

}
