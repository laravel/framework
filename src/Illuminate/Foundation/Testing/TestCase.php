<?php namespace Illuminate\Foundation\Testing;

abstract class TestCase extends \PHPUnit_Framework_TestCase {

	use ApplicationTrait, AssertionsTrait;

	/**
	 * Setup the test environment.
	 *
	 * @return void
	 */
	public function setUp()
	{
		if ( ! $this->app)
		{
			$this->refreshApplication();
		}
	}

	/**
	 * Creates the application.
	 *
	 * Needs to be implemented by subclasses.
	 *
	 * @return \Symfony\Component\HttpKernel\HttpKernelInterface
	 */
	abstract public function createApplication();

}
