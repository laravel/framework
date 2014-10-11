<?php

use Mockery as m;

class FoundationConfigPublishCommandTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		$this->addToAssertionCount(m::getContainer()->mockery_getExpectationCount());

		m::close();
	}


	public function testCommandCallsPublisherWithProperPackageName()
	{
		$command = new Illuminate\Foundation\Console\ConfigPublishCommand($pub = m::mock('Illuminate\Foundation\ConfigPublisher'));
		$pub->shouldReceive('alreadyPublished')->andReturn(false);
		$pub->shouldReceive('publishPackage')->once()->with('foo');
		$command->run(new Symfony\Component\Console\Input\ArrayInput(array('package' => 'foo')), new Symfony\Component\Console\Output\NullOutput);
	}

}
