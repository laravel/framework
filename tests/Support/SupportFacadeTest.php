<?php

class SupportFacadeTest extends PHPUnit_Framework_TestCase {

	public function testFacadeCallsUnderlyingApplication()
	{
		FacadeStub::setFacadeApplication(array('foo' => new ApplicationStub));
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
		return new ApplicationStub;
	}

}

class ApplicationStub {

	public function bar()
	{
		return 'baz';
	}

}