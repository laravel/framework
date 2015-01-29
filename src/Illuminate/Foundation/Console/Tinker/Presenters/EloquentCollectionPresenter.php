<?php namespace Illuminate\Foundation\Console\Tinker\Presenters;

use Psy\Presenter\ObjectPresenter;
use Illuminate\Database\Eloquent\Collection;

class EloquentCollectionPresenter extends ObjectPresenter {

	/**
	 * EloquentCollectionPresenter can present Collections.
	 *
	 * @param  mixed  $value
	 * @return bool
	 */
	public function canPresent($value)
	{
		return $value instanceof Collection;
	}

	/**
	 * Get an array of Collection object properties.
	 *
	 * ReflectionProperty constants may be passed as $propertyFilter, and should
	 * be used to toggle visibility of private and protected properties.
	 *
	 * @param  object  $value
	 * @param  \ReflectionClass  $class
	 * @param  int  $propertyFilter
	 * @return array
	 */
	public function getProperties($value, \ReflectionClass $class, $propertyFilter)
	{
		return $value->toArray();
	}

}
