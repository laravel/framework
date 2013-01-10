<?php

use Mockery as m;

class BeanstalkdJobTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testFireProperlyCallsTheJobHandler()
	{
		$job = $this->getJob();
		$job->getPheanstalkJob()->shouldReceive('getData')->once()->andReturn(serialize(array('job' => 'foo', 'data' => array('data'))));
		$job->getContainer()->shouldReceive('make')->once()->with('foo')->andReturn($handler = m::mock('StdClass'));
		$handler->shouldReceive('fire')->once()->with($job, array('data'));

		$job->fire();
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


	protected function getJob()
	{
		return new Illuminate\Queue\Jobs\BeanstalkdJob(
			m::mock('Illuminate\Container'),
			m::mock('Pheanstalk'),
			m::mock('Pheanstalk_Job')
		);
	}

}