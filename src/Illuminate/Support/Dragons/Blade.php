<?php namespace Illuminate\Support\Dragons;

/**
 * @see \Illuminate\View\Compilers\BladeCompiler
 */
class Blade extends Dragon {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getDragonAccessor()
	{
		return static::$app['view']->getEngineResolver()->resolve('blade')->getCompiler();
	}

}