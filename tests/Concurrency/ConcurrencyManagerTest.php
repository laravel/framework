<?php

namespace Illuminate\Tests\Concurrency;

use Illuminate\Concurrency\ConcurrencyManager;
use Illuminate\Concurrency\ProcessDriver;
use Illuminate\Concurrency\SyncDriver;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use Illuminate\Process\Factory as ProcessFactory;
use Mockery;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Spatie\Fork\Fork;

class ConcurrencyManagerTest extends TestCase
{
    protected function makeApp(array $config = []): Container
    {
        $app = new Container;

        $configRepo = new ConfigRepository($config);

        $app->instance('config', $configRepo);
        $app->instance(ConfigRepository::class, $configRepo);

        return $app;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_returns_default_instance_from_config(): void
    {
        $app = $this->makeApp([
            'concurrency.default' => 'sync',
        ]);

        $manager = new ConcurrencyManager($app);

        $this->assertSame('sync', $manager->getDefaultInstance());
    }

    public function test_it_sets_default_instance_on_config(): void
    {
        $app = $this->makeApp();

        $manager = new ConcurrencyManager($app);
        $manager->setDefaultInstance('process');

        $this->assertSame('process', $app['config']->get('concurrency.default'));
        $this->assertSame('process', $app['config']->get('concurrency.driver'));
    }

    public function test_it_creates_process_driver(): void
    {
        $app = $this->makeApp();

        $app->instance(ProcessFactory::class, Mockery::mock(ProcessFactory::class));

        $manager = new ConcurrencyManager($app);

        $driver = $manager->createProcessDriver([]);

        $this->assertInstanceOf(ProcessDriver::class, $driver);
    }

    public function test_it_creates_sync_driver(): void
    {
        $app = $this->makeApp();

        $manager = new ConcurrencyManager($app);

        $driver = $manager->createSyncDriver([]);

        $this->assertInstanceOf(SyncDriver::class, $driver);
    }

    public function test_it_throws_exception_when_using_fork_driver_in_web_context(): void
    {
        $app = $this->makeApp();

        $app->instance('runningInConsole', false);

        $app->bind('runningInConsole', fn () => false);

        $app = Mockery::mock($app)->makePartial();
        $app->shouldReceive('runningInConsole')->andReturn(false);

        $manager = new ConcurrencyManager($app);

        $this->expectException(RuntimeException::class);

        $manager->createForkDriver([]);
    }

    public function test_it_requires_spatie_fork_package(): void
    {
        if (class_exists(Fork::class)) {
            $this->markTestSkipped('spatie/fork is installed');
        }

        $app = Mockery::mock($this->makeApp())->makePartial();
        $app->shouldReceive('runningInConsole')->andReturn(true);

        $manager = new ConcurrencyManager($app);

        $this->expectException(RuntimeException::class);

        $manager->createForkDriver([]);
    }

    public function test_it_returns_instance_specific_config(): void
    {
        $app = $this->makeApp([
            'concurrency.driver.process' => [
                'driver' => 'process',
            ],
        ]);

        $manager = new ConcurrencyManager($app);

        $this->assertSame(
            ['driver' => 'process'],
            $manager->getInstanceConfig('process')
        );
    }
}
