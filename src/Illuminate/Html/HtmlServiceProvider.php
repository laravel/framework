<?php namespace Illuminate\Html;

use Illuminate\Support\ServiceProvider;

class HtmlServiceProvider extends ServiceProvider {

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
		$this->app['form'] = $this->app->share(function($app)
		{
			$form = new FormBuilder($app['url'], $app['session']->getToken());

			$form->setSessionStore($app['session']);

			return $form;
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('form');
	}

}