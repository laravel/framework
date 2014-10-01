<?php namespace Illuminate\Foundation\Support\Providers;

use Illuminate\Support\ServiceProvider;

class FilterServiceProvider extends ServiceProvider {

	/**
	 * The filters that should run before all requests.
	 *
	 * @var array
	 */
	protected $before = [];

	/**
	 * The filters that should run after all requests.
	 *
	 * @var array
	 */
	protected $after = [];

	/**
	 * All available route filters.
	 *
	 * @var array
	 */
	protected $filters = [];

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$router = $this->app['router'];

		foreach ($this->before as $before)
		{
			$router->before($before);
		}

		foreach ($this->after as $after)
		{
			$router->after($after);
		}

		foreach ($this->filters as $name => $filter)
		{
			$router->filter($name, $filter);
		}
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		//
	}

}
