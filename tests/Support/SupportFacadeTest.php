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
	 * Get the registered component.
	 *
	 * @return string
	 */
	public static function getCurrent()  // facaded Singleton
	{
		return ApplicationStub::getCurrent();
	}

}

class ApplicationStub {

	protected static $Current;  // real Singleton

	public static function getCurrent()
	{
		return static::$Current;
	}

	public function __construct() {
		static::$Current = $this;
	}

	public function bar()
	{
		return 'baz';
	}

}