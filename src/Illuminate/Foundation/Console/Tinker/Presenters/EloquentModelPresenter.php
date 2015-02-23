<?php namespace Illuminate\Foundation\Console\Tinker\Presenters;

use ReflectionClass;
use ReflectionProperty;
use Psy\Presenter\ObjectPresenter;
use Illuminate\Database\Eloquent\Model;

class EloquentModelPresenter extends ObjectPresenter {

	/**
	 * Determine if the presenter can present the given value.
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
	 * @param  object  $value
	 * @param  \ReflectionClass  $class
	 * @param  int  $propertyFilter
	 * @return array
	 */
	public function getProperties($value, ReflectionClass $class, $propertyFilter)
	{
		$attributes = array_merge($value->getAttributes(), $value->getRelations());

		$visible = $value->getVisible();

		if (count($visible) === 0)
		{
			$visible = array_diff(array_keys($attributes), $value->getHidden());
		}

		if ( ! $this->showHidden($propertyFilter))
		{
			return array_intersect_key($attributes, array_flip($visible));
		}

		$properties = [];

		foreach ($attributes as $key => $value)
		{
			if ( ! in_array($key, $visible))
			{
				$key = sprintf('<protected>%s</protected>', $key);
			}

			$properties[$key] = $value;
		}

		return $properties;
	}

	/**
	 * Decide whether to show hidden properties, based on a ReflectionProperty filter.
	 *
	 * @param  int  $propertyFilter
	 * @return bool
	 */
	protected function showHidden($propertyFilter)
	{
		return $propertyFilter & (ReflectionProperty::IS_PROTECTED | ReflectionProperty::IS_PRIVATE);
	}

}
