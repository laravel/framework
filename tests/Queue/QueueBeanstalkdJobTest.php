<?php

use Mockery as m;
use Pheanstalk\Pheanstalk;
use Illuminate\Container\Container;
use Illuminate\Queue\Jobs\BeanstalkdJob;
use Pheanstalk\Job as BeanstalkdBaseJob;

class QueueBeanstalkdJobTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testFireProperlyCallsTheJobHandler()
	{
		$job = $this->getJob();
		$job->getPheanstalkJob()->shouldReceive('getData')->once()->andReturn(json_encode(array('job' => 'foo', 'data' => array('data'))));
		$job->getContainer()->shouldReceive('make')->once()->with('foo')->andReturn($handler = m::mock('StdClass'));
		$handler->shouldReceive('fire')->once()->with($job, array('data'));

		$job->fire();
	}


	public function testFailedProperlyCallsTheJobHandler()
	{
		$job = $this->getJob();
		$job->getPheanstalkJob()->shouldReceive('getData')->once()->andReturn(json_encode(array('job' => 'foo', 'data' => array('data'))));
		$job->getContainer()->shouldReceive('make')->once()->with('foo')->andReturn($handler = m::mock('BeanstalkdJobTestFailedTest'));
		$handler->shouldReceive('failed')->once()->with(array('data'));

		$job->failed();
	}


	public function testDeleteRemovesTheJobFromBeanstalkd()
	{
		$job = $this->getJob();
		$job->getPheanstalk()->shouldReceive('delete')->once()->with($job->getPheanstalkJob());

		$job->delete();
	}


	public function testReleaseProperlyReleasesJobOntoBeanstalkd()
	{
		$job = $this->getJob();
		$job->getPheanstalk()->shouldReceive('release')->once()->with($job->getPheanstalkJob(), Pheanstalk::DEFAULT_PRIORITY, 0);

		$job->release();
	}


	public function testBuryProperlyBuryTheJobFromBeanstalkd()
	{
		$job = $this->getJob();
		$job->getPheanstalk()->shouldReceive('bury')->once()->with($job->getPheanstalkJob());

		$job->bury();
	}


	protected function getJob()
	{
		return new BeanstalkdJob(
			m::mock(Container::class),
			m::mock(Pheanstalk::class),
			m::mock(BeanstalkdBaseJob::class),
			'default'
		);
	}

}

class BeanstalkdJobTestFailedTest {
	public function failed(array $data)
	{
		//
	}
}
