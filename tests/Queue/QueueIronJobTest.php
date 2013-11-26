<?php

use Mockery as m;

class QueueIronJobTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testFireProperlyCallsTheJobHandler()
	{
		$job = $this->getJob();
		$job->getContainer()->shouldReceive('make')->once()->with('foo')->andReturn($handler = m::mock('StdClass'));
		$handler->shouldReceive('fire')->once()->with($job, array('data'));

		$job->fire();
	}


	public function testDeleteRemovesTheJobFromIron()
	{
		$job = $this->getJob();
		$job->getIron()->shouldReceive('deleteMessage')->once()->with('default', 1);

		$job->delete();
	}


	public function testDeleteNoopsOnPushedQueues()
	{
		$job = new Illuminate\Queue\Jobs\IronJob(
			m::mock('Illuminate\Container\Container'),
			m::mock('IronMQ'),
			m::mock('Illuminate\Encryption\Encrypter'),
			(object) array('id' => 1, 'body' => json_encode(array('job' => 'foo', 'data' => array('data'))), 'timeout' => 60, 'pushed' => true),
			'default'
		);
		$job->getIron()->shouldReceive('deleteMessage')->never();

		$job->delete();
	}


	public function testReleaseProperlyReleasesJobOntoIron()
	{
		$job = new Illuminate\Queue\Jobs\IronJob(
			m::mock('Illuminate\Container\Container'),
			m::mock('IronMQ'),
			$crypt = m::mock('Illuminate\Encryption\Encrypter', array('encrypt')),
			(object) array('id' => 1, 'body' => json_encode(array('job' => 'foo', 'data' => array('data'), 'attempts' => 1, 'queue' => 'default')), 'timeout' => 60)
		);

		$job->getIron()->shouldReceive('deleteMessage')->once();
		$job->getIron()->shouldReceive('postMessage')->once()->with('default', 'encrypted', array('delay' => 5));
		$crypt->shouldReceive('encrypt')->once()->with(json_encode(array('job' => 'foo', 'data' => array('data'), 'attempts' => 2, 'queue' => 'default')))->andReturn('encrypted');

		$job->release(5);
	}


	protected function getJob()
	{
		return new Illuminate\Queue\Jobs\IronJob(
			m::mock('Illuminate\Container\Container'),
			m::mock('IronMQ'),
			m::mock('Illuminate\Encryption\Encrypter', array('encrypt')),
			(object) array('id' => 1, 'body' => json_encode(array('job' => 'foo', 'data' => array('data'), 'attempts' => 1, 'queue' => 'default')), 'timeout' => 60)
		);
	}

}