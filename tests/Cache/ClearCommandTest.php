<?php

namespace Illuminate\Tests\Cache;

use Illuminate\Cache\CacheManager;
use Illuminate\Cache\Console\ClearCommand;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use InvalidArgumentException;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class ClearCommandTest extends TestCase
{
    /**
     * @var \Illuminate\Tests\Cache\ClearCommandTestStub
     */
    private $command;

    /**
     * @var \Illuminate\Cache\CacheManager|\Mockery\MockInterface
     */
    private $cacheManager;

    /**
     * @var \Illuminate\Filesystem\Filesystem|\Mockery\MockInterface
     */
    private $files;

    /**
     * @var \Illuminate\Contracts\Cache\Repository|\Mockery\MockInterface
     */
    private $cacheRepository;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheManager = m::mock(CacheManager::class);
        $this->files = m::mock(Filesystem::class);
        $this->cacheRepository = m::mock(Repository::class);
        $this->command = new ClearCommandTestStub($this->cacheManager, $this->files);

        $app = new Application;
        $app['path.storage'] = __DIR__;
        $this->command->setLaravel($app);
    }

    protected function tearDown(): void
    {
        m::close();
    }

    public function testClearWithNoStoreArgument()
    {
        $this->files->shouldReceive('exists')->andReturn(true);
        $this->files->shouldReceive('files')->andReturn([]);

        $this->cacheManager->shouldReceive('store')->once()->with(null)->andReturn($this->cacheRepository);
        $this->cacheRepository->shouldReceive('flush')->once();

        $this->runCommand($this->command);
    }

    public function testClearWithStoreArgument()
    {
        $this->files->shouldReceive('exists')->andReturn(true);
        $this->files->shouldReceive('files')->andReturn([]);

        $this->cacheManager->shouldReceive('store')->once()->with('foo')->andReturn($this->cacheRepository);
        $this->cacheRepository->shouldReceive('flush')->once();

        $this->runCommand($this->command, ['store' => 'foo']);
    }

    public function testClearWithInvalidStoreArgument()
    {
        $this->expectException(InvalidArgumentException::class);

        $this->files->shouldReceive('files')->andReturn([]);

        $this->cacheManager->shouldReceive('store')->once()->with('bar')->andThrow(InvalidArgumentException::class);
        $this->cacheRepository->shouldReceive('flush')->never();

        $this->runCommand($this->command, ['store' => 'bar']);
    }

    public function testClearWithTagsOption()
    {
        $this->files->shouldReceive('exists')->andReturn(true);
        $this->files->shouldReceive('files')->andReturn([]);

        $this->cacheManager->shouldReceive('store')->once()->with(null)->andReturn($this->cacheRepository);
        $this->cacheRepository->shouldReceive('tags')->once()->with(['foo', 'bar'])->andReturn($this->cacheRepository);
        $this->cacheRepository->shouldReceive('flush')->once();

        $this->runCommand($this->command, ['--tags' => 'foo,bar']);
    }

    public function testClearWithStoreArgumentAndTagsOption()
    {
        $this->files->shouldReceive('exists')->andReturn(true);
        $this->files->shouldReceive('files')->andReturn([]);

        $this->cacheManager->shouldReceive('store')->once()->with('redis')->andReturn($this->cacheRepository);
        $this->cacheRepository->shouldReceive('tags')->once()->with(['foo'])->andReturn($this->cacheRepository);
        $this->cacheRepository->shouldReceive('flush')->once();

        $this->runCommand($this->command, ['store' => 'redis', '--tags' => 'foo']);
    }

    public function testClearWillClearRealTimeFacades()
    {
        $this->cacheManager->shouldReceive('store')->once()->with(null)->andReturn($this->cacheRepository);
        $this->cacheRepository->shouldReceive('flush')->once();

        $this->files->shouldReceive('exists')->andReturn(true);
        $this->files->shouldReceive('files')->andReturn(['/facade-XXXX.php']);
        $this->files->shouldReceive('delete')->with('/facade-XXXX.php')->once();

        $this->runCommand($this->command);
    }

    public function testClearWillNotClearRealTimeFacadesIfCacheDirectoryDoesntExist()
    {
        $this->cacheManager->shouldReceive('store')->once()->with(null)->andReturn($this->cacheRepository);
        $this->cacheRepository->shouldReceive('flush')->once();

        // No files should be looped over and nothing should be deleted if the cache directory doesn't exist
        $this->files->shouldReceive('exists')->andReturn(false);
        $this->files->shouldNotReceive('files');
        $this->files->shouldNotReceive('delete');

        $this->runCommand($this->command);
    }

    protected function runCommand($command, $input = [])
    {
        return $command->run(new ArrayInput($input), new NullOutput);
    }
}

class ClearCommandTestStub extends ClearCommand
{
    public function call($command, array $arguments = [])
    {
        return 0;
    }
}
