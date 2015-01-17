<?php

use Mockery as m;
use Illuminate\Console\Scheduling\Event;

class ConsoleScheduledEventTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testBasicCronCompilation()
	{
		$app = m::mock('Illuminate\Foundation\Application[isDownForMaintenance,environment]');
		$app->shouldReceive('isDownForMaintenance')->andReturn(false);
		$app->shouldReceive('environment')->andReturn('production');

		$event = new Event('php foo');
		$this->assertEquals('* * * * * *', $event->getExpression());
		$this->assertTrue($event->isDue($app));
		$this->assertFalse($event->skip(function() { return true; })->isDue($app));

		$event = new Event('php foo');
		$this->assertEquals('* * * * * *', $event->getExpression());
		$this->assertFalse($event->environments('local')->isDue($app));

		$event = new Event('php foo');
		$this->assertEquals('0 0 * * * *', $event->daily()->getExpression());
		$this->assertFalse($event->when(function() { return true; })->isDue($app));

		$event = new Event('php foo');
		$this->assertEquals('*/5 * * * * *', $event->everyFiveMinutes()->getExpression());

		$event = new Event('php foo');
		$this->assertEquals('*/5 * * * 3 *', $event->everyFiveMinutes()->wednesdays()->getExpression());

		$event = new Event('php foo');
		$this->assertEquals('0 * * * * *', $event->everyFiveMinutes()->hourly()->getExpression());
	}

}
