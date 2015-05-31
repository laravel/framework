<?php

use Mockery as m;
use Illuminate\Queue\SyncQueue;
use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Container\Container;
use Illuminate\Encryption\Encrypter;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\EntityResolver;
use Illuminate\Contracts\Queue\QueueableEntity;
use Illuminate\Contracts\Encryption\Encrypter as EncrypterContract;

class QueueSyncQueueTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testPushShouldFireJobInstantly()
	{
		unset($_SERVER['__sync.test']);

		/**
		 * Test Synced Closure
		 */
		$sync = new SyncQueue;
		$container = new Container;
		$encrypter = new Encrypter(str_random(32));
		$container->instance(EncrypterContract::class, $encrypter);
		$sync->setContainer($container);
		$sync->setEncrypter($encrypter);
		$sync->push(function($job) {
			$_SERVER['__sync.test'] = true;
			$job->delete();
		});

		$this->assertTrue($_SERVER['__sync.test']);
		unset($_SERVER['__sync.test']);

		/**
		 * Test Synced Class Handler
		 */
		$sync->push('SyncQueueTestHandler', ['foo' => 'bar']);
		$this->assertInstanceOf(SyncJob::class, $_SERVER['__sync.test'][0]);
		$this->assertEquals(['foo' => 'bar'], $_SERVER['__sync.test'][1]);
	}


	public function testQueueableEntitiesAreSerializedAndResolved()
	{
		$sync = new SyncQueue;
		$sync->setContainer($container = new Container);
		$container->instance(EntityResolver::class, $resolver = m::mock(EntityResolver::class));
		$resolver->shouldReceive('resolve')->once()->with('SyncQueueTestEntity', 1)->andReturn(new SyncQueueTestEntity);
		$sync->push('SyncQueueTestHandler', ['entity' => new SyncQueueTestEntity]);

		$this->assertInstanceOf('SyncQueueTestEntity', $_SERVER['__sync.test'][1]['entity']);
	}


	public function testQueueableEntitiesAreSerializedAndResolvedWhenPassedAsSingleEntities()
	{
		$sync = new SyncQueue;
		$sync->setContainer($container = new Container);
		$container->instance(EntityResolver::class, $resolver = m::mock(EntityResolver::class));
		$resolver->shouldReceive('resolve')->once()->with('SyncQueueTestEntity', 1)->andReturn(new SyncQueueTestEntity);
		$sync->push('SyncQueueTestHandler', new SyncQueueTestEntity);

		$this->assertInstanceOf('SyncQueueTestEntity', $_SERVER['__sync.test'][1]);
	}


	public function testFailedJobGetsHandledWhenAnExceptionIsThrown()
	{
		unset($_SERVER['__sync.failed']);

		$sync = new SyncQueue;
		$container = new Container;
		$encrypter = new Encrypter(str_random(32));
		$container->instance(EncrypterContract::class, $encrypter);
		$events = m::mock(Dispatcher::class);
		$events->shouldReceive('fire')->once();
		$container->instance('events', $events);
		$sync->setContainer($container);
		$sync->setEncrypter($encrypter);

		try {
			$sync->push('FailingSyncQueueTestHandler', ['foo' => 'bar']);
		}
		catch(Exception $e)
		{
			$this->assertTrue($_SERVER['__sync.failed']);
		}
	}

}

class SyncQueueTestEntity implements QueueableEntity {
	public function getQueueableId() {
		return 1;
	}
}

class SyncQueueTestHandler {
	public function fire($job, $data) {
		$_SERVER['__sync.test'] = func_get_args();
	}
}

class FailingSyncQueueTestHandler {
	public function fire($job, $data) {
		throw new Exception();
	}
	public function failed(){
		$_SERVER['__sync.failed'] = true;
	}
}
