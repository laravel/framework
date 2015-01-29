<?php namespace Illuminate\Foundation\Console\Tinker\Presenters;

use Psy\Presenter\ObjectPresenter;
use Illuminate\Database\Eloquent\Collection;

class EloquentCollectionPresenter extends ObjectPresenter {

	/**
	 * EloquentCollectionPresenter can present Collections.
	 *
	 * @param mixed $value
	 * @return boolean
	 */
	public function canPresent($value)
	{
		return $value instanceof Collection;
	}

	/**
	 * Get an array of Collection object properties.
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
