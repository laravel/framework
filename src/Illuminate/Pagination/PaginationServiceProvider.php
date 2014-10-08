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
		Paginator::setCurrentPathResolver(function()
		{
			return $this->app['request']->url();
		});

		Paginator::setCurrentPageResolver(function()
		{
			return $this->app['request']->input('page');
		});
	}

}
