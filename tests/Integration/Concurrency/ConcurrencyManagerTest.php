<?php

namespace Illuminate\Tests\Integration\Concurrency;

use Illuminate\Concurrency\ConcurrencyManager;
use Illuminate\Concurrency\ForkDriver;
use Illuminate\Concurrency\ProcessDriver;
use Illuminate\Concurrency\SyncDriver;
use Illuminate\Foundation\Application;
use Illuminate\Process\Factory as ProcessFactory;
use Mockery;
use Orchestra\Testbench\TestCase;
use RuntimeException;
use Spatie\Fork\Fork;

class ConcurrencyManagerTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
    }

    protected function defineEnvironment($app)
    {
        // Set up the config for concurrency
        $app['config']->set('concurrency.default', 'sync');
        $app['config']->set('concurrency.driver.process', ['driver' => 'process']);
    }

    public function testDriverReturnsTheDefaultDriver()
    {
        $manager = new ConcurrencyManager($this->app);

        $this->assertInstanceOf(SyncDriver::class, $manager->driver());
    }

    public function testManagerCanCreateSyncDriver()
    {
        $manager = new ConcurrencyManager($this->app);
        $driver = $manager->driver('sync');

        $this->assertInstanceOf(SyncDriver::class, $driver);
    }

    public function testManagerCanCreateProcessDriver()
    {
        $this->app->instance(ProcessFactory::class, Mockery::mock(ProcessFactory::class));

        $manager = new ConcurrencyManager($this->app);
        $driver = $manager->driver('process');

        $this->assertInstanceOf(ProcessDriver::class, $driver);
    }

    public function testCreateForkDriverThrowsExceptionInWebRequest()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Due to PHP limitations, the fork driver may not be used within web requests.');

        // Create partial mock of the application to override runningInConsole
        $appMock = Mockery::mock($this->app)->makePartial();
        $appMock->shouldReceive('runningInConsole')->andReturn(false);

        $manager = new ConcurrencyManager($appMock);
        $manager->driver('fork');
    }

    public function testCreateForkDriverThrowsExceptionWhenPackageIsMissing()
    {
        // Skip this test if Spatie Fork package is installed
        if (class_exists(Fork::class)) {
            $this->markTestSkipped('Spatie Fork package is installed');
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Please install the "spatie/fork" Composer package in order to utilize the "fork" driver.');

        $manager = new ConcurrencyManager($this->app);
        $manager->driver('fork');
    }

    public function testCreateForkDriverWhenAvailable()
    {
        // Skip this test if the package is not installed
        if (! class_exists(Fork::class)) {
            $this->markTestSkipped('Spatie Fork package is not installed');
        }

        $manager = new ConcurrencyManager($this->app);
        $driver = $manager->driver('fork');

        $this->assertInstanceOf(ForkDriver::class, $driver);
    }

    public function testSetDefaultInstance()
    {
        $manager = new ConcurrencyManager($this->app);

        $manager->setDefaultInstance('fork');

        $this->assertEquals('fork', $this->app['config']['concurrency.default']);
        $this->assertEquals('fork', $this->app['config']['concurrency.driver']);
    }

    public function testGetInstanceConfig()
    {
        $this->app['config']->set('concurrency.driver.process', ['timeout' => 60]);

        $manager = new ConcurrencyManager($this->app);
        $config = $manager->getInstanceConfig('process');

        $this->assertEquals(['timeout' => 60], $config);
    }

    public function testGetInstanceConfigWithDefaultsWhenNotConfigured()
    {
        $this->app['config']->set('concurrency.driver.process', null);

        $manager = new ConcurrencyManager($this->app);
        $config = $manager->getInstanceConfig('custom');

        $this->assertEquals(['driver' => 'custom'], $config);
    }

    public function testItCanBeUsedAsAConcurrencyDriver()
    {
        $manager = new ConcurrencyManager($this->app);

        // Test direct method calls on the manager
        $result = $manager->run(fn () => 5);

        $this->assertEquals([0 => 5], $result);
    }

    public function testItCreatesRealForkDriverWhenPackageIsAvailable()
    {
        // No need for "runningInConsole" check in a real test - since TestCase always returns runningInConsole

        // Test the existence of the Fork class that is checked in ConcurrencyManager
        if (! class_exists(\Spatie\Fork\Fork::class)) {
            $this->markTestSkipped('Spatie Fork package is not installed');
        }

        $manager = new ConcurrencyManager($this->app);
        $config = [];

        // With ReflectionClass we can access the protected method
        $reflection = new \ReflectionClass(ConcurrencyManager::class);
        $method = $reflection->getMethod('createForkDriver');
        $method->setAccessible(true);

        $driver = $method->invoke($manager, $config);

        $this->assertInstanceOf(ForkDriver::class, $driver);
    }
}
