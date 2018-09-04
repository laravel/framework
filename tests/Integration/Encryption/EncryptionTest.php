<?php

namespace Illuminate\Tests\Integration\Encryption;

use RuntimeException;
use Orchestra\Testbench\TestCase;
use Illuminate\Encryption\Encrypter;
use Illuminate\Encryption\EncryptionServiceProvider;

class EncryptionTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.key', 'base64:IUHRqAQ99pZ0A1MPjbuv1D6ff3jxv0GIvS2qIW4JNU4=');
    }

    protected function getPackageProviders($app)
    {
        return [EncryptionServiceProvider::class];
    }

    /**
     * @test
     */
    public function encryption_provider_bind()
    {
        self::assertInstanceOf(Encrypter::class, $this->app->make('encrypter'));
    }

    /**
     * @test
     */
    public function encryption_will_not_be_instantiable_when_missing_app_key()
    {
        $this->expectException(RuntimeException::class);

        $this->app['config']->set('app.key', null);

        $this->app->make('encrypter');
    }
}
