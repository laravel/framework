<?php

namespace Illuminate\Tests\Console;

use Illuminate\Cache\ArrayStore;
use Illuminate\Console\CacheCommandMutex;
use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Factory;
use Illuminate\Contracts\Cache\Repository;
use JMac\Testing\Double;
use PHPUnit\Framework\TestCase;

class CacheCommandMutexTest extends TestCase
{
    /**
     * @var \Illuminate\Console\CacheCommandMutex
     */
    protected $mutex;

    /**
     * @var \Illuminate\Console\Command
     */
    protected $command;

    /**
     * @var \Illuminate\Contracts\Cache\Factory
     */
    protected $cacheFactory;

    /**
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cacheRepository;

    protected function setUp(): void
    {
        $this->cacheFactory = Double::for(Factory::class);
        $this->cacheRepository = Double::for(Repository::class);
        $this->mutex = new CacheCommandMutex($this->cacheFactory);
        $this->command = new class extends Command
        {
            protected $name = 'command-name';
        };
    }

    public function testCanCreateMutex()
    {
        $this->mockUsingCacheStore();
        $this->cacheRepository->expects('add')->returns(true);
        $actual = $this->mutex->create($this->command);

        $this->assertTrue($actual);
    }

    public function testCannotCreateMutexIfAlreadyExist()
    {
        $this->mockUsingCacheStore();
        $this->cacheRepository->expects('add')->returns(false);
        $actual = $this->mutex->create($this->command);

        $this->assertFalse($actual);
    }

    public function testCanCreateMutexWithCustomConnection()
    {
        $this->cacheFactory->expects('store')->with('test')->returns($this->cacheRepository);
        $this->cacheRepository->expects('getStore')->returns(null);
        $this->cacheRepository->expects('add')->returns(false);
        $this->mutex->useStore('test');

        $actual = $this->mutex->create($this->command);

        $this->assertFalse($actual);
    }

    public function testCanCreateMutexWithLockProvider()
    {
        $this->cacheFactory->expects('store')->returns($this->cacheRepository);
        $this->cacheRepository->expects('getStore')->times(2)->returns(new ArrayStore);

        $actual = $this->mutex->create($this->command);

        $this->assertTrue($actual);
    }

    public function testCannotCreateMutexIfAlreadyExistWithLockProvider()
    {
        $store = new ArrayStore;
        $this->cacheFactory->expects('store')->times(2)->returns($this->cacheRepository);
        $this->cacheRepository->expects('getStore')->times(4)->returns($store);

        $this->mutex->create($this->command);
        $actual = $this->mutex->create($this->command);

        $this->assertFalse($actual);
    }

    public function testCanCreateMutexWithCustomConnectionWithLockProvider()
    {
        $this->cacheFactory->expects('store')->with('test')->returns($this->cacheRepository);
        $this->cacheRepository->expects('getStore')->times(2)->returns(new ArrayStore);
        $this->mutex->useStore('test');

        $actual = $this->mutex->create($this->command);

        $this->assertTrue($actual);
    }

    /**
     * @return void
     */
    private function mockUsingCacheStore(): void
    {
        $this->cacheFactory->expects('store')->returns($this->cacheRepository);
        $this->cacheRepository->expects('getStore')->returns(null);
    }

    public function testCommandMutexNameWithoutIsolatedMutexNameMethod()
    {
        $this->mockUsingCacheStore();

        $this->cacheRepository->expects('add')
            ->withArgs(function ($key) {
                $this->assertSame('framework'.DIRECTORY_SEPARATOR.'command-command-name', $key);

                return true;
            })
            ->andReturn(true);

        $this->mutex->create($this->command);
    }

    public function testCommandMutexNameWithIsolatedMutexNameMethod()
    {
        $command = new class extends Command
        {
            protected $name = 'command-name';

            public function isolatableId()
            {
                return 'isolated';
            }
        };

        $this->mockUsingCacheStore();

        $this->cacheRepository->expects('add')
            ->withArgs(function ($key) {
                $this->assertSame('framework'.DIRECTORY_SEPARATOR.'command-command-name-isolated', $key);

                return true;
            })
            ->andReturn(true);

        $this->mutex->create($command);
    }
}
