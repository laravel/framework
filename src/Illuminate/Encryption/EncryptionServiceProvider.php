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
		$this->app->bindShared('encrypter', function($app)
		{
			$fileName = isset($app['config']['app.seedfile'])? $app['config']['app.seedfile'] : 'seed-file';
			$seedFullPath = $app['path.storage'] . DIRECTORY_SEPARATOR . $fileName;
			$encrypter =  new Encrypter($app['config']['app.key'], $seedFullPath);

			if ($app['config']->has('app.cipher'))
			{
				$encrypter->setCipher($app['config']['app.cipher']);
			}

			return $encrypter;
		});
	}

}
