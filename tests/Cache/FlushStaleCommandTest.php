<?php

namespace Illuminate\Tests\Cache;

use Illuminate\Cache\CacheManager;
use Illuminate\Cache\Console\FlushStaleCommand;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Foundation\Application;
use InvalidArgumentException;
use Mockery as m;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class FlushStaleCommandTest extends TestCase
{
    /**
     * @var FlushStaleCommand
     */
    private $command;

    /**
     * @var CacheManager|MockInterface
     */
    private $cacheManager;

    /**
     * @var Repository|MockInterface
     */
    private $cacheRepository;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheManager = m::mock(CacheManager::class);
        $this->cacheRepository = m::mock(Repository::class);
        $this->command = new FlushStaleCommand($this->cacheManager);

        $app = new Application;
        $app['path.storage'] = __DIR__;
        $this->command->setLaravel($app);
    }

    protected function tearDown(): void
    {
        m::close();
    }

    public function testFlushWithNoStoreArgument()
    {
        $this->cacheManager->shouldReceive('store')->once()->with(null)->andReturn($this->cacheRepository);
        $this->cacheRepository->shouldReceive('supportsFlushingStale')->once()->andReturnTrue();
        $this->cacheRepository->shouldReceive('flushStale')->once();

        $this->runCommand($this->command);
    }

    public function testFlushWithStoreArgument()
    {
        $this->cacheManager->shouldReceive('store')->once()->with('foo')->andReturn($this->cacheRepository);
        $this->cacheRepository->shouldReceive('supportsFlushingStale')->once()->andReturnTrue();
        $this->cacheRepository->shouldReceive('flushStale')->once();

        $this->runCommand($this->command, ['store' => 'foo']);
    }

    public function testFlushWithNonFlushableStore()
    {
        $this->cacheManager->shouldReceive('store')->once()->with(null)->andReturn($this->cacheRepository);
        $this->cacheRepository->shouldReceive('supportsFlushingStale')->once()->andReturnFalse();
        $this->cacheRepository->shouldReceive('flushStale')->never();

        $this->runCommand($this->command);
    }

    public function testClearWithInvalidStoreArgument()
    {
        $this->expectException(InvalidArgumentException::class);

        $this->cacheManager->shouldReceive('store')->once()->with('bar')->andThrow(InvalidArgumentException::class);
        $this->cacheRepository->shouldReceive('flushStale')->never();

        $this->runCommand($this->command, ['store' => 'bar']);
    }

    protected function runCommand($command, $input = [])
    {
        return $command->run(new ArrayInput($input), new NullOutput);
    }
}
