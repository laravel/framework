<?php

class FacadeTest extends PHPUnit_Framework_TestCase {

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
	protected static function getFacadeAccessor()
	{
		return 'foo';
	}

}

class ApplicationStub {

	public function bar()
	{
		return 'baz';
	}

}