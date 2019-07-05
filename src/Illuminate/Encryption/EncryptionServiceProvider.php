<?php

namespace Illuminate\Encryption;

use RuntimeException;
use Illuminate\Support\Str;
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
            return new EncryptionManager($app);
        });
//        $this->app->singleton('encrypter.default', function ($app) {
//            return $app['encrypter']->encrypter();
//        });
    }
}
