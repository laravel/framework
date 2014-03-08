<?php namespace Illuminate\Support\NotFacades;

/**
 * @see \Illuminate\View\Compilers\BladeCompiler
 */
class Blade extends NotAFacade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getNotAFacadeAccessor()
	{
		return static::$app['view']->getEngineResolver()->resolve('blade')->getCompiler();
	}

}