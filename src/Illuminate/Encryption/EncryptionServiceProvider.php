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
            $config = $app->make('config')->get('app');

            if (Str::startsWith($key = $config['key'], 'base64:')) {
                $key = base64_decode(substr($key, 7));
            }

            return $this->getEncrypterForKeyAndCipher($key, $config['cipher']);
        });
    }

    /**
     * Get the proper encrypter instance for the given key and cipher.
     *
     * @param  string  $key
     * @param  string  $cipher
     * @return mixed
     *
     * @throws \RuntimeException
     */
    protected function getEncrypterForKeyAndCipher($key, $cipher)
    {
        if (Encrypter::supported($key, $cipher)) {
            return new Encrypter($key, $cipher);
        } elseif (McryptEncrypter::supported($key, $cipher)) {
            return new McryptEncrypter($key, $cipher);
        } else {
            throw new RuntimeException('No supported encrypter found. The cipher and / or key length are invalid.');
        }
    }
}
