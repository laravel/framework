<?php namespace Illuminate\Foundation\Console\Tinker\Presenters;

use Psy\Presenter\ObjectPresenter;
use Illuminate\Database\Eloquent\Model;

class EloquentModelPresenter extends ObjectPresenter {

	/**
	 * EloquentModelPresenter can present Models.
	 *
	 * @param mixed $value
	 * @return boolean
	 */
	public function canPresent($value)
	{
		return $value instanceof Model;
	}

	/**
	 * Get an array of Model object properties.
	 *
	 * @param object           $value
	 * @param \ReflectionClass $class
     * @param int              $propertyFilter One of \ReflectionProperty constants
	 * @return array
	 */
	public function getProperties($value, \ReflectionClass $class, $propertyFilter)
	{
		return $value->toArray();
	}
}
