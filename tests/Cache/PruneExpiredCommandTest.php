<?php

namespace Illuminate\Tests\Cache;

use Illuminate\Cache\CacheManager;
use Illuminate\Cache\Console\PruneExpiredCommand;
use Illuminate\Cache\DatabaseStore;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Foundation\Application;
use Mockery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class PruneExpiredCommandTest extends TestCase
{
    /**
     * @var \Illuminate\Cache\Console\PruneExpiredCommand
     */
    private $command;

    /**
     * @var \Illuminate\Cache\CacheManager|\Mockery\MockInterface
     */
    private $cacheManager;

    /**
     * @var \Illuminate\Contracts\Cache\Repository|\Mockery\MockInterface
     */
    private $cacheRepository;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->cacheManager = Mockery::mock(CacheManager::class);
        $this->cacheRepository = Mockery::mock(Repository::class);
        $this->command = new PruneExpiredCommand;

        $app = new Application;
        $app->instance(CacheManager::class, $this->cacheManager);
        $this->command->setLaravel($app);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testPruneWithNoStoreArgument()
    {
        $store = Mockery::mock(DatabaseStore::class);
        $store->expects('pruneExpired')->andReturn(3);

        $this->cacheManager->expects('store')->with(null)->andReturn($this->cacheRepository);
        $this->cacheRepository->expects('getStore')->andReturn($store);

        $this->assertSame(0, $this->runCommand($this->command));
    }

    public function testPruneWithStoreArgument()
    {
        $store = Mockery::mock(DatabaseStore::class);
        $store->expects('pruneExpired')->andReturn(3);

        $this->cacheManager->expects('store')->with('foo')->andReturn($this->cacheRepository);
        $this->cacheRepository->expects('getStore')->andReturn($store);

        $this->assertSame(0, $this->runCommand($this->command, ['store' => 'foo']));
    }

    public function testPruneWillFailWhenNotSupportedByStore()
    {
        $store = Mockery::mock(Store::class);

        $this->cacheManager->expects('store')->with(null)->andReturn($this->cacheRepository);
        $this->cacheRepository->expects('getStore')->andReturn($store);

        $this->assertSame(1, $this->runCommand($this->command));
    }

    protected function runCommand($command, $input = [])
    {
        return $command->run(new ArrayInput($input), new NullOutput);
    }
}
