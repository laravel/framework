<?php

namespace Illuminate\Encryption;

use Illuminate\Support\ServiceProvider;

class EncryptionServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('encrypter', function ($app) {
            $config = $app['config'];
            
            return new Encrypter($config->get('app.key'), $config->get('app.cipher', 'AES-128-CBC'));
        });
    }
}
