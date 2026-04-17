<?php

namespace Illuminate\Tests\Foundation;

use Illuminate\Contracts\Http\Kernel as HttpKernelContract;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\ApplicationBuilder;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Http\Middleware\PrefersJsonResponses;
use PHPUnit\Framework\TestCase;

class FoundationApplicationBuilderTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_ENV['APP_BASE_PATH'], $_ENV['LARAVEL_STORAGE_PATH'], $_SERVER['LARAVEL_STORAGE_PATH']);

        parent::tearDown();
    }

    public function testBaseDirectoryWithArg()
    {
        $_ENV['APP_BASE_PATH'] = __DIR__.'/as-env';

        $app = Application::configure(__DIR__.'/as-arg')->create();

        $this->assertSame(__DIR__.'/as-arg', $app->basePath());
    }

    public function testBaseDirectoryWithEnv()
    {
        $_ENV['APP_BASE_PATH'] = __DIR__.'/as-env';

        $app = Application::configure()->create();

        $this->assertSame(__DIR__.'/as-env', $app->basePath());
    }

    public function testBaseDirectoryWithComposer()
    {
        $app = Application::configure()->create();

        $this->assertSame(dirname(__DIR__, 2), $app->basePath());
    }

    public function testStoragePathWithGlobalEnvVariable()
    {
        $_ENV['LARAVEL_STORAGE_PATH'] = __DIR__.'/env-storage';

        $app = Application::configure()->create();

        $this->assertSame(__DIR__.'/env-storage', $app->storagePath());
    }

    public function testStoragePathWithGlobalServerVariable()
    {
        $_SERVER['LARAVEL_STORAGE_PATH'] = __DIR__.'/server-storage';

        $app = Application::configure()->create();

        $this->assertSame(__DIR__.'/server-storage', $app->storagePath());
    }

    public function testStoragePathPrefersEnvVariable()
    {
        $_ENV['LARAVEL_STORAGE_PATH'] = __DIR__.'/env-storage';
        $_SERVER['LARAVEL_STORAGE_PATH'] = __DIR__.'/server-storage';

        $app = Application::configure()->create();

        $this->assertSame(__DIR__.'/env-storage', $app->storagePath());
    }

    public function testStoragePathBasedOnBasePath()
    {
        $app = Application::configure()->create();
        $this->assertSame($app->basePath().DIRECTORY_SEPARATOR.'storage', $app->storagePath());
    }

    public function testStoragePathCanBeCustomized()
    {
        $_ENV['LARAVEL_STORAGE_PATH'] = __DIR__.'/env-storage';

        $app = Application::configure()->create();
        $app->useStoragePath(__DIR__.'/custom-storage');

        $this->assertSame(__DIR__.'/custom-storage', $app->storagePath());
    }

    public function testPrefersJsonResponsesIsFluent()
    {
        $builder = Application::configure();

        $this->assertSame($builder, $builder->prefersJsonResponses());
        $this->assertSame($builder, $builder->prefersJsonResponses(false));
    }

    public function testPrefersJsonResponsesRegistersMiddlewareWhenEnabled()
    {
        $app = Application::configure()->prefersJsonResponses()->create();

        $this->assertTrue($this->bootAndResolveKernel($app)->hasMiddleware(PrefersJsonResponses::class));
    }

    public function testPrefersJsonResponsesDefaultsToDisabled()
    {
        $app = Application::configure()->create();

        $this->assertFalse($this->bootAndResolveKernel($app)->hasMiddleware(PrefersJsonResponses::class));
    }

    public function testPrefersJsonResponsesIsIdempotentWhenCalledMultipleTimes()
    {
        $app = Application::configure()->prefersJsonResponses()->prefersJsonResponses()->create();

        $this->assertTrue($this->bootAndResolveKernel($app)->hasMiddleware(PrefersJsonResponses::class));
    }

    public function testPrefersJsonResponsesFalseDoesNotRegisterMiddleware()
    {
        $app = Application::configure()->prefersJsonResponses(false)->create();

        $this->assertFalse($this->bootAndResolveKernel($app)->hasMiddleware(PrefersJsonResponses::class));
    }

    protected function bootAndResolveKernel(Application $app): HttpKernel
    {
        $app->singleton(HttpKernelContract::class, HttpKernel::class);

        // The builder registers its wiring inside $app->booted() callbacks.
        // We can't call $app->boot() from a unit test — it runs the full
        // provider chain which expects a real application — so invoke the
        // booted callbacks directly. Real boot behavior is covered by the
        // PrefersJson integration tests.
        $reflection = new \ReflectionClass(Application::class);
        $property = $reflection->getProperty('bootedCallbacks');
        $property->setAccessible(true);

        foreach ($property->getValue($app) as $callback) {
            $callback($app);
        }

        return $app->make(HttpKernelContract::class);
    }
}
