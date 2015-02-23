<?php namespace Illuminate\Foundation\Console\Tinker\Presenters;

use Exception;
use ReflectionClass;
use Psy\Presenter\ObjectPresenter;
use Illuminate\Foundation\Application;

class IlluminateApplicationPresenter extends ObjectPresenter {

	/**
	 * Illuminate Application methods to include in the presenter.
	 *
	 * @var array
	 */
	protected static $appProperties = [
		'configurationIsCached',
		'environment',
		'environmentFile',
		'isLocal',
		'routesAreCached',
		'runningUnitTests',
		'version',
		'path',
		'basePath',
		'configPath',
		'databasePath',
		'langPath',
		'publicPath',
		'storagePath',
	];

	/**
	 * Can the presenter present the given value?
	 *
	 * @param  mixed  $value
	 * @return bool
	 */
	public function canPresent($value)
	{
		return $value instanceof Application;
	}

	/**
	 * Get an array of Application object properties.
	 *
	 * ReflectionProperty constants may be passed as $propertyFilter, and should
	 * be used to toggle visibility of private and protected properties.
	 *
	 * @param  object  $value
	 * @param  ReflectionClass  $class
	 * @param  int  $propertyFilter
	 * @return array
	 */
	public function getProperties($value, ReflectionClass $class, $propertyFilter)
	{
		$properties = [];

		foreach (self::$appProperties as $property)
		{
			try
			{
				$val = $value->$property();
				if ( ! is_null($val)) $properties[$property] = $val;
			}
			catch (Exception $e)
			{
				// Ignore exceptions
			}
		}

		return $properties;
	}

}
