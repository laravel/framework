<?php

namespace Illuminate\Tests\Cache;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Application;
use Illuminate\Cache\Console\ClearCommand;

class ClearCommandTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testClearWithNoStoreArgument()
    {
        $command = new ClearCommandTestStub(
            $cacheManager = m::mock('Illuminate\Cache\CacheManager'),
            $files = m::mock('Illuminate\Filesystem\Filesystem')
        );

        $cacheRepository = m::mock('Illuminate\Contracts\Cache\Repository');

        $app = new Application;
        $app['path.storage'] = __DIR__;
        $command->setLaravel($app);
        $files->shouldReceive('files')->andReturn([]);

        $cacheManager->shouldReceive('store')->once()->with(null)->andReturn($cacheRepository);
        $cacheRepository->shouldReceive('flush')->once();

        $this->runCommand($command);
    }

    public function testClearWithStoreArgument()
    {
        $command = new ClearCommandTestStub(
            $cacheManager = m::mock('Illuminate\Cache\CacheManager'),
            $files = m::mock('Illuminate\Filesystem\Filesystem')
        );

        $cacheRepository = m::mock('Illuminate\Contracts\Cache\Repository');

        $app = new Application;
        $app['path.storage'] = __DIR__;
        $command->setLaravel($app);
        $files->shouldReceive('files')->andReturn([]);

        $cacheManager->shouldReceive('store')->once()->with('foo')->andReturn($cacheRepository);
        $cacheRepository->shouldReceive('flush')->once();

        $this->runCommand($command, ['store' => 'foo']);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage
     */
    public function testClearWithInvalidStoreArgument()
    {
        $command = new ClearCommandTestStub(
            $cacheManager = m::mock('Illuminate\Cache\CacheManager'),
            $files = m::mock('Illuminate\Filesystem\Filesystem')
        );

        $cacheRepository = m::mock('Illuminate\Contracts\Cache\Repository');

        $app = new Application;
        $app['path.storage'] = __DIR__;
        $command->setLaravel($app);
        $files->shouldReceive('files')->andReturn([]);

        $cacheManager->shouldReceive('store')->once()->with('bar')->andThrow('InvalidArgumentException');
        $cacheRepository->shouldReceive('flush')->never();

        $this->runCommand($command, ['store' => 'bar']);
    }

    public function testClearWithTagsOption()
    {
        $command = new ClearCommandTestStub(
            $cacheManager = m::mock('Illuminate\Cache\CacheManager'),
            $files = m::mock('Illuminate\Filesystem\Filesystem')
        );

        $cacheRepository = m::mock('Illuminate\Contracts\Cache\Repository');

        $app = new Application;
        $app['path.storage'] = __DIR__;
        $command->setLaravel($app);
        $files->shouldReceive('files')->andReturn([]);

        $cacheManager->shouldReceive('store')->once()->with(null)->andReturn($cacheRepository);
        $cacheRepository->shouldReceive('tags')->once()->with(['foo', 'bar'])->andReturn($cacheRepository);
        $cacheRepository->shouldReceive('flush')->once();

        $this->runCommand($command, ['--tags' => 'foo,bar']);
    }

    public function testClearWithStoreArgumentAndTagsOption()
    {
        $command = new ClearCommandTestStub(
            $cacheManager = m::mock('Illuminate\Cache\CacheManager'),
            $files = m::mock('Illuminate\Filesystem\Filesystem')
        );

        $cacheRepository = m::mock('Illuminate\Contracts\Cache\Repository');

        $app = new Application;
        $app['path.storage'] = __DIR__;
        $command->setLaravel($app);
        $files->shouldReceive('files')->andReturn([]);

        $cacheManager->shouldReceive('store')->once()->with('redis')->andReturn($cacheRepository);
        $cacheRepository->shouldReceive('tags')->once()->with(['foo'])->andReturn($cacheRepository);
        $cacheRepository->shouldReceive('flush')->once();

        $this->runCommand($command, ['store' => 'redis', '--tags' => 'foo']);
    }

    public function testClearWillClearsRealTimeFacades()
    {
        $command = new ClearCommandTestStub(
            $cacheManager = m::mock('Illuminate\Cache\CacheManager'),
            $files = m::mock('Illuminate\Filesystem\Filesystem')
        );

        $cacheRepository = m::mock('Illuminate\Contracts\Cache\Repository');

        $app = new Application;
        $app['path.storage'] = __DIR__;
        $command->setLaravel($app);
        $cacheManager->shouldReceive('store')->once()->with(null)->andReturn($cacheRepository);
        $cacheRepository->shouldReceive('flush')->once();

        $files->shouldReceive('files')->andReturn(['/facade-XXXX.php']);
        $files->shouldReceive('delete')->with('/facade-XXXX.php')->once();

        $this->runCommand($command);
    }

    protected function runCommand($command, $input = [])
    {
        return $command->run(new \Symfony\Component\Console\Input\ArrayInput($input), new \Symfony\Component\Console\Output\NullOutput);
    }
}

class ClearCommandTestStub extends ClearCommand
{
    public function call($command, array $arguments = [])
    {
        //
    }
}
