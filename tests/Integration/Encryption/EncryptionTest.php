<?php

namespace Illuminate\Tests\Integration\Encryption;

use Illuminate\Contracts\Encryption\EncryptException;
use Illuminate\Encryption\EncryptionManager;
use Orchestra\Testbench\TestCase;
use Illuminate\Encryption\EncryptionServiceProvider;

class EncryptionTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.encryption', [
            'driver' => 'openssl',
            'cipher' => 'AES-256-CBC',
            'key'    => 'base64:IUHRqAQ99pZ0A1MPjbuv1D6ff3jxv0GIvS2qIW4JNU4='
        ]);
    }

    protected function getPackageProviders($app)
    {
        return [EncryptionServiceProvider::class];
    }

    public function test_encryption_provider_bind()
    {
        self::assertInstanceOf(EncryptionManager::class, $this->app->make('encrypter'));
    }

    public function test_generate_key()
    {
        $e = $this->app->make('encrypter');

        $this->app['config']->set('app.encryption.key', $e->generateKey());

        $e = $this->app->make('encrypter');

        $e->encrypt('bar');
    }

    public function test_encryption_will_not_be_usable_when_missing_app_key()
    {
        $this->expectException(EncryptException::class);

        $this->app['config']->set('app.encryption.key', null);

        $e = $this->app->make('encrypter');

        $e->encrypt('bar');
    }
}
