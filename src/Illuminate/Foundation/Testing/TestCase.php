<?php namespace Illuminate\Foundation\Testing;

use PHPUnit_Framework_TestCase;

abstract class TestCase extends PHPUnit_Framework_TestCase {

	use ApplicationTrait, AssertionsTrait;

	/**
	 * Creates the application.
	 *
	 * Needs to be implemented by subclasses.
	 *
	 * @return \Symfony\Component\HttpKernel\HttpKernelInterface
	 */
	abstract public function createApplication();

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
	 * Clean up the testing environment before the next test.
	 *
	 * @return void
	 */
	public function tearDown()
	{
		if ($this->app)
		{
			$this->app->flush();
		}
	}

}
