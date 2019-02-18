<?php

namespace Illuminate\Tests\Foundation\Bootstrap;

use Exception;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;

class LoadConfigurationTest extends TestCase
{
    /**
     * @var string
     */
    protected $defaultEncoding;

    /**
     * @var string
     */
    protected $defaultTimezone;

    protected function setUp(): void
    {
        $this->defaultEncoding = mb_internal_encoding();
        $this->defaultTimezone = date_default_timezone_get();
    }

    protected function tearDown(): void
    {
        mb_internal_encoding($this->defaultEncoding);
        date_default_timezone_set($this->defaultTimezone);
    }

    public function testLoadFromCachedFile()
    {
        $app = new Application(realpath(__DIR__.'/../fixtures/app_with_cached_config'));

        (new LoadConfiguration)->bootstrap($app);

        $this->assertSame('Config Cache Name', $app['config']->get('app.name'));
        $this->assertSame('Config Cache Version', $app['config']->get('app.version'));

        $this->assertSame('UTF-8', mb_internal_encoding());
        $this->assertSame($app['config']->get('app.env', 'production'), $app->environment());
        $this->assertSame($app['config']->get('app.timezone', 'UTC'), date_default_timezone_get());
    }

    public function testLoadFromFiles()
    {
        $app = new Application(realpath(__DIR__.'/../fixtures/app_without_cached_config'));

        (new LoadConfiguration)->bootstrap($app);

        $this->assertEquals(require $app->configPath('app.php'), $app['config']->get('app'));
        $this->assertEquals(require $app->configPath('database.php'), $app['config']->get('database'));
        $this->assertEquals(require $app->configPath('nested/example.php'), $app['config']->get('nested.example'));

        $this->assertSame('UTF-8', mb_internal_encoding());
        $this->assertSame($app['config']->get('app.env', 'production'), $app->environment());
        $this->assertSame($app['config']->get('app.timezone', 'UTC'), date_default_timezone_get());
    }

    public function testCannotLoadWithoutCoreConfig()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unable to load the "app" configuration file.');

        $app = new Application(realpath(__DIR__.'/../fixtures/app_without_core_config'));

        (new LoadConfiguration)->bootstrap($app);
    }
}
