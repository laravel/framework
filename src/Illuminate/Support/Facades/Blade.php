<?php namespace Illuminate\Support\Facades;

class Blade extends Facade {

	/**
	 * Resolve a BladeCompiler from the registered component 'view'.
	 *
	 * @return Illuminate\View\Compilers\BladeCompiler
	 */
	public static function Current() {
		return Illuminate\Foundation\Application::Current()['view']->getEngineResolver()->resolve('blade')->getCompiler();
	}

}