<?php namespace Illuminate\Container;

use Exception;

class CircularReferenceException extends Exception {

	/**
	 * The build stack that caused the exception.
	 *
	 * @var array
	 */
	protected $buildStack;

	/**
	 * Create a new circular reference exception instance.
	 *
	 * @param  string  $class
	 * @param  array  $buildStack
	 *
	 * @return \Illuminate\Container\CircularReferenceException
	 */
	public function __construct($class, array $buildStack)
	{
		$this->message = "Circular reference found while resolving [$class].";
		$this->buildStack = $buildStack;
	}

	/**
	 * Get the build stack that caused the exception
	 *
	 * @return  array
	 */
	public function getBuildStack()
	{
		return $this->buildStack;
	}

}
