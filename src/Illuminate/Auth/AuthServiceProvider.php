<?php namespace Illuminate\Auth;

use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider {

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->registerAuthenticator();

		$this->registerUserResolver();

		$this->registerRequestRebindHandler();
	}

	/**
	 * Register the authenticator services.
	 *
	 * @return void
	 */
	protected function registerAuthenticator()
	{
		$this->app->singleton('auth', function()
		{
			// Once the authentication service has actually been requested by the developer
			// we will set a variable in the application indicating such. This helps us
			// know that we need to set any queued cookies in the after event later.
			$this->app['auth.loaded'] = true;

			return new AuthManager($this->app);
		});

		$this->app->singleton('auth.driver', function()
		{
			return $this->app['auth']->driver();
		});
	}

	/**
	 * Register a resolver for the authenticated user.
	 *
	 * @return void
	 */
	protected function registerUserResolver()
	{
		$this->app->bind('Illuminate\Contracts\Auth\Authenticatable', function()
		{
			return $this->app['auth']->user();
		});
	}

	/**
	 * Register a resolver for the authenticated user.
	 *
	 * @return void
	 */
	protected function registerRequestRebindHandler()
	{
		$this->app->rebinding('request', function($request)
		{
			$request->setUserResolver(function()
			{
				return $this->app['auth']->user();
			});
		});
	}

}
