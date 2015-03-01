<?php namespace Illuminate\Container;

use Exception;

class CircularReferenceException extends Exception {

	/**
	 * @var array
	 */
	protected $buildStack;

	/**
	 * @param string $class
	 * @param array $buildStack
	 *
	 * @return static
	 */
	public static function in($class, $buildStack)
	{
		return with(new static("Circular reference found while resolving [$class]."))->setBuildStack($buildStack);
	}

	/**
	 * @param $buildStack
	 */
	public function setBuildStack($buildStack)
	{
		$this->buildStack = $buildStack;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getBuildStack()
	{
		return $this->buildStack;
	}

}
