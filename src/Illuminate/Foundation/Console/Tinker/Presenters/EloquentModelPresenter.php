<?php namespace Illuminate\Foundation\Console\Tinker\Presenters;

use Psy\Presenter\ObjectPresenter;
use Illuminate\Database\Eloquent\Model;

class EloquentModelPresenter extends ObjectPresenter {

	/**
	 * EloquentModelPresenter can present Models.
	 *
	 * @param  mixed  $value
	 * @return bool
	 */
	public function canPresent($value)
	{
		return $value instanceof Model;
	}

	/**
	 * Get an array of Model object properties.
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
		$attributes = $value->getAttributes();
		$visible = $value->getVisible();

		if (count($visible) > 0) return array_intersect_key($attributes, array_flip($visible));

		return array_diff_key($attributes, array_flip($value->getHidden()));
	}

}
