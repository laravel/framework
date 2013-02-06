<?php

class SupportFacadeTest extends PHPUnit_Framework_TestCase {

	public function testFacadeCallsUnderlyingApplication()
	{
		new ApplicationStub;
		$this->assertEquals('baz', FacadeStub::bar());
	}

}

class FacadeStub extends Illuminate\Support\Facades\Facade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	public static function getCurrent()
	{
		return ApplicationStub::getCurrent();
	}

}

class ApplicationStub {

	protected $Current;

	public static function getCurrent()
	{
		return static::$Current;
	}

	public function __construct() {
		$Current = $this;
	}

	public function bar()
	{
		return 'baz';
	}

}