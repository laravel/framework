<?php

namespace Illuminate\Tests\Integration\Encryption;

use RuntimeException;
use Orchestra\Testbench\TestCase;
use Illuminate\Encryption\Encrypter;
use Illuminate\Encryption\EncryptionManager;
use Illuminate\Encryption\EncryptionServiceProvider;

class EncryptionTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('encryption.default', 'default');
        $app['config']->set('encryption.encrypters.default.key', 'base64:IUHRqAQ99pZ0A1MPjbuv1D6ff3jxv0GIvS2qIW4JNU4=');
        $app['config']->set('encryption.encrypters.default.cipher', 'AES-256-CBC');
    }

    protected function getPackageProviders($app)
    {
        return [EncryptionServiceProvider::class];
    }

    public function test_encryption_provider_bind()
    {
        self::assertInstanceOf(EncryptionManager::class, $this->app->make('encrypter'));
    }

    public function test_encryption_manager_returns_encrypter()
    {
        self::assertInstanceOf(Encrypter::class, $this->app->make('encrypter')->encrypter());
    }

    public function test_encryption_manager_returns_specific_encrypter()
    {
        $this->app['config']->set('encryption.encrypters.foo.key', 'base64:IUHRqAQ99pZ0A1MPjbuv1D6ff3jxv0GIvS2qIW4JNU4=');
        $this->app['config']->set('encryption.encrypters.foo.cipher', 'AES-256-CBC');

        self::assertInstanceOf(Encrypter::class, $this->app->make('encrypter')->encrypter('foo'));
    }

    public function test_encryption_will_not_be_instantiable_when_missing_app_key()
    {
        $this->expectException(RuntimeException::class);

        $this->app['config']->set('encryption.encrypters.default.key', null);

        $this->app->make('encrypter')->encrypter();
    }

    public function test_encryption_manager_will_not_return_encrypter_if_config_is_missing()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->app['config']->set('encryption.encrypters', null);

        $this->app->make('encrypter')->encrypter();
    }
}
