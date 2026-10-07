<?php

namespace Illuminate\Tests\Cache;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\CacheManager;
use Illuminate\Cache\Console\ClearCommand;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\CanFlushLocks;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use InvalidArgumentException;
use JMac\Testing\Double;
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
     * @var \Illuminate\Cache\CacheManager|DoubleInterface
     */
    private $cacheManager;

    /**
     * @var \Illuminate\Filesystem\Filesystem|DoubleInterface
     */
    private $files;

    /**
     * @var Repository
     */
    private $cacheRepository;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->cacheManager = Double::for(CacheManager::class);
        $this->files = Double::for(Filesystem::class);
        $this->cacheRepository = new Repository(new ArrayStore);
        $this->command = new ClearCommandTestStub($this->cacheManager, $this->files);

        $app = new Application;
        $app['path.storage'] = __DIR__;
        $this->command->setLaravel($app);
    }

    public function testClearWithNoStoreArgument()
    {
        $this->files->expects('exists')->returns(true);
        $this->files->expects('files')->returns([]);

        $this->cacheRepository->put('foo', 'bar');
        $this->cacheManager->expects('store')->with(null)->returns($this->cacheRepository);

        $this->runCommand($this->command);

        $this->assertNull($this->cacheRepository->get('foo'));
    }

    public function testClearWithStoreArgument()
    {
        $this->files->expects('exists')->returns(true);
        $this->files->expects('files')->returns([]);

        $this->cacheRepository->put('foo', 'bar');
        $this->cacheManager->expects('store')->with('foo')->returns($this->cacheRepository);

        $this->runCommand($this->command, ['store' => 'foo']);

        $this->assertNull($this->cacheRepository->get('foo'));
    }

    public function testClearWithInvalidStoreArgument()
    {
        $this->expectException(InvalidArgumentException::class);

        $this->cacheManager->expects('store')->with('bar')->throws(new InvalidArgumentException());

        $this->runCommand($this->command, ['store' => 'bar']);
    }

    public function testClearWithTagsOption()
    {
        $this->files->expects('exists')->returns(true);
        $this->files->expects('files')->returns([]);

        $this->cacheRepository->tags(['foo', 'bar'])->put('tagged', 'value');
        $this->cacheRepository->put('untagged', 'value');
        $this->cacheManager->expects('store')->with(null)->returns($this->cacheRepository);

        $this->runCommand($this->command, ['--tags' => 'foo,bar']);

        $this->assertNull($this->cacheRepository->tags(['foo', 'bar'])->get('tagged'));
        $this->assertSame('value', $this->cacheRepository->get('untagged'));
    }

    public function testClearWithStoreArgumentAndTagsOption()
    {
        $this->files->expects('exists')->returns(true);
        $this->files->expects('files')->returns([]);

        $this->cacheRepository->tags(['foo'])->put('tagged', 'value');
        $this->cacheRepository->put('untagged', 'value');
        $this->cacheManager->expects('store')->with('redis')->returns($this->cacheRepository);

        $this->runCommand($this->command, ['store' => 'redis', '--tags' => 'foo']);

        $this->assertNull($this->cacheRepository->tags(['foo'])->get('tagged'));
        $this->assertSame('value', $this->cacheRepository->get('untagged'));
    }

    public function testClearWillClearRealTimeFacades()
    {
        $this->cacheManager->expects('store')->with(null)->returns($this->cacheRepository);

        $this->files->expects('exists')->returns(true);
        $this->files->expects('files')->returns(['/facade-XXXX.php']);
        $this->files->expects('delete')->with('/facade-XXXX.php');

        $this->runCommand($this->command);
    }

    public function testClearWillNotClearRealTimeFacadesIfCacheDirectoryDoesntExist()
    {
        $this->cacheManager->expects('store')->with(null)->returns($this->cacheRepository);

        // No files should be looped over and nothing should be deleted if the cache directory doesn't exist
        $this->files->expects('exists')->returns(false);
        $this->files->expects('files')->never();
        $this->files->expects('delete')->never();

        $this->runCommand($this->command);
    }

    public function testClearLocksWithNoStoreArgument()
    {
        $store = $this->lockFlushingStore();
        $store->expects('flushLocks')->returns(true);
        $store->expects('flush')->never();
        $this->cacheManager->expects('store')->with(null)->returns(new Repository($store));

        $this->files->expects('exists')->never();
        $this->files->expects('files')->never();
        $this->files->expects('delete')->never();

        $this->assertSame(0, $this->runCommand($this->command, ['--locks' => true]));
    }

    public function testClearLocksWithStoreArgument()
    {
        $store = $this->lockFlushingStore();
        $store->expects('flushLocks')->returns(true);
        $store->expects('flush')->never();
        $this->cacheManager->expects('store')->with('redis')->returns(new Repository($store));

        $this->assertSame(0, $this->runCommand($this->command, ['store' => 'redis', '--locks' => true]));
    }

    public function testClearLocksCannotBeUsedWithTags()
    {
        $this->cacheManager->expects('store')->never();

        $this->assertSame(1, $this->runCommand($this->command, ['--locks' => true, '--tags' => 'foo']));
    }

    public function testClearLocksWillFailWhenNotSupportedByStore()
    {
        $store = Double::for(Store::class);
        $store->expects('flush')->never();
        $this->cacheManager->expects('store')->with(null)->returns(new Repository($store));

        $this->assertSame(1, $this->runCommand($this->command, ['--locks' => true]));
    }

    public function testClearLocksWillFailWhenFlushLocksFails()
    {
        $store = $this->lockFlushingStore();
        $store->expects('flushLocks')->returns(false);
        $store->expects('flush')->never();
        $this->cacheManager->expects('store')->with(null)->returns(new Repository($store));

        $this->assertSame(1, $this->runCommand($this->command, ['--locks' => true]));
    }

    protected function lockFlushingStore()
    {
        return Double::for(Store::class, CanFlushLocks::class);
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
