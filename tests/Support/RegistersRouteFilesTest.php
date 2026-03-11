<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Traits\RegistersRouteFiles;
use InvalidArgumentException;
use Orchestra\Testbench\TestCase;

// Concrete stub to test the trait
class RouteFileServiceProviderStub extends ServiceProvider
{
    public function boot(): void {}
    public function register(): void {}

    public static function resetRegistry(): void
    {
        static::$registeredRouteFiles = [];
    }
}

class RegistersRouteFilesTest extends TestCase
{
    protected RouteFileServiceProviderStub $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new RouteFileServiceProviderStub($this->app);

        RouteFileServiceProviderStub::resetRegistry();
    }

    /** @test */
    public function it_throws_for_a_missing_file(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/could not be found/');

        $this->provider->registerRouteFiles('nonexistent');
    }

    /** @test */
    public function it_auto_appends_php_extension(): void
    {
        $this->withTempRouteFile('api.php', function () {
            $this->provider->registerRouteFiles('api'); // no .php — should still work
            $this->assertTrue(true);
        });
    }

    /** @test */
    public function it_does_not_double_append_extension(): void
    {
        $this->withTempRouteFile('api.php', function () {
            $this->provider->registerRouteFiles('api.php');
            $this->assertTrue(true);
        });
    }

    /** @test */
    public function it_loads_multiple_files_from_array(): void
    {
        $this->withTempRouteFile('web.php', function () {
            $this->withTempRouteFile('api.php', function () {
                $this->provider->registerRouteFiles(['web', 'api']);
                $this->assertTrue(true);
            });
        });
    }

    /** @test */
    public function it_does_not_load_same_file_twice(): void
    {
        $loaded = 0;

        $this->withTempRouteFile('web.php', function () use (&$loaded) {
            $this->provider->registerRouteFiles('web');
            $this->provider->registerRouteFiles('web'); // second call — ignored

            $loaded = count(RouteFileServiceProviderStub::$registeredRouteFiles);
        });

        $this->assertSame(1, $loaded);
    }

    /** @test */
    public function it_returns_itself_for_fluent_chaining(): void
    {
        $this->withTempRouteFile('web.php', function () {
            $this->withTempRouteFile('api.php', function () {
                $result = $this->provider
                    ->registerRouteFiles('web')
                    ->registerRouteFiles('api');

                $this->assertSame($this->provider, $result);
            });
        });
    }

    /** @test */
    public function it_resolves_subdirectory_paths(): void
    {
        $this->withTempRouteFile('admin/dashboard.php', function () {
            $this->provider->registerRouteFiles('admin/dashboard');
            $this->assertTrue(true);
        });
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    protected function withTempRouteFile(string $relative, \Closure $callback): void
    {
        $base = sys_get_temp_dir() . '/laravel_test_' . uniqid();
        $dir  = $base . '/routes/' . dirname($relative);

        @mkdir($dir, 0777, true);
        file_put_contents($base . '/routes/' . $relative, '<?php');

        $this->app->instance('path.base', $base);

        try {
            $callback();
        } finally {
            @unlink($base . '/routes/' . $relative);
            @rmdir($dir);
            @rmdir($base . '/routes');
            @rmdir($base);
        }
    }
}