<?php

class SupportNowTest extends PHPUnit_Framework_TestCase {

	public function testNowReturnsDatetime()
	{
		$datetime = now();
		$this->assertInstanceOf('Datetime', $datetime);
	}


	public function testNowSkipUnknownTimezone()
	{
		$datetime = now('foo');
		$defaultTimezone = new DateTimeZone(date_default_timezone_get());
		$this->assertEquals($defaultTimezone, $datetime->getTimezone());
	}


	public function testNowHandlesTimezone()
	{
		$datetime = now('Europe/Berlin');
		$defaultTimezone = new DateTimeZone('Europe/Berlin');
		$this->assertEquals($defaultTimezone, $datetime->getTimezone());
	}


	public function testNowProvidesActualDatetime()
	{
		$datetime = now();
		$before = new DateTime('yesterday');
		$after = new DateTime('tomorrow');
		$this->assertGreaterThan($before, $datetime);
		$this->assertLessThan($after, $datetime);
	}

}
