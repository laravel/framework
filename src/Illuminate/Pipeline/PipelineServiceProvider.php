<?php namespace Illuminate\Pipeline;

use Illuminate\Support\ServiceProvider;

class PipelineServiceProvider extends ServiceProvider {

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
		$this->app->singleton(
			'Illuminate\Contracts\Pipeline\Hub', 'Illuminate\Pipeline\Hub'
		);
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return [
			'Illuminate\Contracts\Pipeline\Hub',
		];
	}

}
