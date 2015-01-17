<?php namespace Illuminate\Translation;

use Illuminate\Support\ServiceProvider;

class TranslationServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = true;

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->registerLoader();

		$this->app->singleton('translator', function()
		{
			$loader = $this->app['translation.loader'];

			// When registering the translator component, we'll need to set the default
			// locale as well as the fallback locale. So, we'll grab the application
			// configuration so we can easily get both of these values from there.
			$locale = $this->app['config']['app.locale'];

			$trans = new Translator($loader, $locale);

			$trans->setFallback($this->app['config']['app.fallback_locale']);

			return $trans;
		});
	}

	/**
	 * Register the translation line loader.
	 *
	 * @return void
	 */
	protected function registerLoader()
	{
		$this->app->singleton('translation.loader', function()
		{
			return new FileLoader($this->app['files'], $this->app['path.lang']);
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('translator', 'translation.loader');
	}

}
