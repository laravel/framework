<?php namespace Illuminate\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Connectors\ConnectionFactory;

class DatabaseServiceProvider extends ServiceProvider {

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		// The connection factory is used to create the actual connection instances on
		// the database. We will inject the factory into the manager so that it may
		// make the connections while they are actually needed and not of before.
		$this->app['db.factory'] = $this->app->share(function()
		{
			return new ConnectionFactory;
		});

		// The database manager is used to resolve various connections, since multiple
		// connections might be managed. It also implements the connection resolver
		// interface which may be used by other components requiring connections.
		$this->app['db'] = $this->app->share(function($app)
		{
			return new DatabaseManager($app, $app['db.factory']);
		});

		$this->registerEloquent();
	}

	/**
	 * Register the database connections with the Eloquent ORM.
	 *
	 * @return void
	 */
	protected function registerEloquent()
	{
		Model::setConnectionResolver($this->app['db']);
	}

}