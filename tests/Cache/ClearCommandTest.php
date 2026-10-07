<?php

namespace Illuminate\Tests\Cache;

use JMac\Testing\Double;
use BadMethodCallException;
use Illuminate\Cache\CacheManager;
use Illuminate\Cache\Console\ClearCommand;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use InvalidArgumentException;
use Mockery;
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
        $this->cacheManager = Double::for(CacheManager::class);
        $this->files = Double::for(Filesystem::class);
        $this->cacheRepository = Double::for(Repository::class);
        $this->command = new ClearCommandTestStub($this->cacheManager, $this->files);

        $app = new Application;
        $app['path.storage'] = __DIR__;
        $this->command->setLaravel($app);
    }

    public function testClearWithNoStoreArgument()
    {
        $this->files->expects('exists')->returns(true);
        $this->files->expects('files')->returns([]);

        $this->cacheManager->expects('store')->with(null)->returns($this->cacheRepository);
        $this->cacheRepository->expects('flush');

        $this->runCommand($this->command);
    }

    public function testClearWithStoreArgument()
    {
        $this->files->expects('exists')->returns(true);
        $this->files->expects('files')->returns([]);

        $this->cacheManager->expects('store')->with('foo')->returns($this->cacheRepository);
        $this->cacheRepository->expects('flush');

        $this->runCommand($this->command, ['store' => 'foo']);
    }

    public function testClearWithInvalidStoreArgument()
    {
        $this->expectException(InvalidArgumentException::class);

        $this->cacheManager->expects('store')->with('bar')->throws(new InvalidArgumentException());
        $this->cacheRepository->expects('flush')->never();

        $this->runCommand($this->command, ['store' => 'bar']);
    }

    public function testClearWithTagsOption()
    {
        $this->files->expects('exists')->returns(true);
        $this->files->expects('files')->returns([]);

        $this->cacheManager->expects('store')->with(null)->returns($this->cacheRepository);
        $this->cacheRepository->expects('tags')->with(['foo', 'bar'])->returns($this->cacheRepository);
        $this->cacheRepository->expects('flush');

        $this->runCommand($this->command, ['--tags' => 'foo,bar']);
    }

    public function testClearWithStoreArgumentAndTagsOption()
    {
        $this->files->expects('exists')->returns(true);
        $this->files->expects('files')->returns([]);

        $this->cacheManager->expects('store')->with('redis')->returns($this->cacheRepository);
        $this->cacheRepository->expects('tags')->with(['foo'])->returns($this->cacheRepository);
        $this->cacheRepository->expects('flush');

        $this->runCommand($this->command, ['store' => 'redis', '--tags' => 'foo']);
    }

    public function testClearWillClearRealTimeFacades()
    {
        $this->cacheManager->expects('store')->with(null)->returns($this->cacheRepository);
        $this->cacheRepository->expects('flush');

        $this->files->expects('exists')->returns(true);
        $this->files->expects('files')->returns(['/facade-XXXX.php']);
        $this->files->expects('delete')->with('/facade-XXXX.php');

        $this->runCommand($this->command);
    }

    public function testClearWillNotClearRealTimeFacadesIfCacheDirectoryDoesntExist()
    {
        $this->cacheManager->expects('store')->with(null)->returns($this->cacheRepository);
        $this->cacheRepository->expects('flush');

        // No files should be looped over and nothing should be deleted if the cache directory doesn't exist
        $this->files->expects('exists')->returns(false);
        $this->files->expects('files')->never();
        $this->files->expects('delete')->never();

        $this->runCommand($this->command);
    }

    public function testClearLocksWithNoStoreArgument()
    {
        $this->cacheManager->expects('store')->with(null)->returns($this->cacheRepository);
        $this->cacheRepository->expects('flushLocks')->returns(true);
        $this->cacheRepository->expects('flush')->never();

        $this->files->expects('exists')->never();
        $this->files->expects('files')->never();
        $this->files->expects('delete')->never();

        $this->assertSame(0, $this->runCommand($this->command, ['--locks' => true]));
    }

    public function testClearLocksWithStoreArgument()
    {
        $this->cacheManager->expects('store')->with('redis')->returns($this->cacheRepository);
        $this->cacheRepository->expects('flushLocks')->returns(true);
        $this->cacheRepository->expects('flush')->never();

        $this->assertSame(0, $this->runCommand($this->command, ['store' => 'redis', '--locks' => true]));
    }

    public function testClearLocksCannotBeUsedWithTags()
    {
        $this->cacheManager->expects('store')->never();
        $this->cacheRepository->expects('flush')->never();
        $this->cacheRepository->expects('flushLocks')->never();

        $this->assertSame(1, $this->runCommand($this->command, ['--locks' => true, '--tags' => 'foo']));
    }

    public function testClearLocksWillFailWhenNotSupportedByStore()
    {
        $this->cacheManager->expects('store')->with(null)->returns($this->cacheRepository);
        $this->cacheRepository->expects('flushLocks')->throws(new BadMethodCallException);
        $this->cacheRepository->expects('flush')->never();

        $this->assertSame(1, $this->runCommand($this->command, ['--locks' => true]));
    }

    public function testClearLocksWillFailWhenFlushLocksFails()
    {
        $this->cacheManager->expects('store')->with(null)->returns($this->cacheRepository);
        $this->cacheRepository->expects('flushLocks')->returns(false);
        $this->cacheRepository->expects('flush')->never();

        $this->assertSame(1, $this->runCommand($this->command, ['--locks' => true]));
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
