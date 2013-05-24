<?php namespace Illuminate\Encryption;

use Illuminate\Support\ServiceProvider;

class EncryptionServiceProvider extends ServiceProvider {

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app['encrypter'] = $this->app->share(function($app)
		{
			return new Encrypter($app['config']['app.key']);
		});
	}

}