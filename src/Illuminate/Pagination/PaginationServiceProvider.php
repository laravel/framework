<?php namespace Illuminate\Pagination;

use Illuminate\Support\ServiceProvider;

class PaginationServiceProvider extends ServiceProvider {

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		Paginator::currentPathResolver(function()
		{
			return $this->app['request']->url();
		});

		Paginator::currentPageResolver(function()
		{
			return $this->app['request']->input('page');
		});
	}

}
