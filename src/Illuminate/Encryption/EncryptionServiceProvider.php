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
		$this->app->singleton('encrypter', function()
		{
			$encrypter =  new Encrypter($this->app['config']['app.key']);

			if ($this->app['config']->has('app.cipher'))
			{
				$encrypter->setCipher($this->app['config']['app.cipher']);
			}

			return $encrypter;
		});
	}

}
