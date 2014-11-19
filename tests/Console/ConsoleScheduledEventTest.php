<?php

use Illuminate\Console\Scheduling\Event;

class ConsoleScheduledEventTest extends PHPUnit_Framework_TestCase {

	public function testBasicCronCompilation()
	{
		$event = new Event('php foo');
		$this->assertEquals('* * * * * *', $event->getExpression());
		$this->assertEquals('0 0 * * * *', $event->daily()->getExpression());
		$this->assertEquals('*/5 * * * * *', $event->everyFiveMinutes()->getExpression());
		$this->assertEquals('*/5 * * * 3 *', $event->everyFiveMinutes()->wednesdays()->getExpression());
		$this->assertEquals('0 * * * * *', $event->everyFiveMinutes()->hourly()->getExpression());
	}

}
