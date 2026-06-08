<?php

namespace Illuminate\Tests\Cache;

use BadMethodCallException;
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

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        ClearCommand::prohibit(false);

        parent::tearDown();
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

    public function testClearLocksWithNoStoreArgument()
    {
        $this->cacheManager->shouldReceive('store')->once()->with(null)->andReturn($this->cacheRepository);
        $this->cacheRepository->shouldReceive('flushLocks')->once()->andReturn(true);
        $this->cacheRepository->shouldNotReceive('flush');

        $this->files->shouldNotReceive('exists');
        $this->files->shouldNotReceive('files');
        $this->files->shouldNotReceive('delete');

        $this->assertSame(0, $this->runCommand($this->command, ['--locks' => true]));
    }

    public function testClearLocksWithStoreArgument()
    {
        $this->cacheManager->shouldReceive('store')->once()->with('redis')->andReturn($this->cacheRepository);
        $this->cacheRepository->shouldReceive('flushLocks')->once()->andReturn(true);
        $this->cacheRepository->shouldNotReceive('flush');

        $this->assertSame(0, $this->runCommand($this->command, ['store' => 'redis', '--locks' => true]));
    }

    public function testClearLocksCannotBeUsedWithTags()
    {
        $this->cacheManager->shouldNotReceive('store');
        $this->cacheRepository->shouldNotReceive('flush');
        $this->cacheRepository->shouldNotReceive('flushLocks');

        $this->assertSame(1, $this->runCommand($this->command, ['--locks' => true, '--tags' => 'foo']));
    }

    public function testClearLocksWillFailWhenNotSupportedByStore()
    {
        $this->cacheManager->shouldReceive('store')->once()->with(null)->andReturn($this->cacheRepository);
        $this->cacheRepository->shouldReceive('flushLocks')->once()->andThrow(new BadMethodCallException);
        $this->cacheRepository->shouldNotReceive('flush');

        $this->assertSame(1, $this->runCommand($this->command, ['--locks' => true]));
    }

    public function testClearLocksWillFailWhenFlushLocksFails()
    {
        $this->cacheManager->shouldReceive('store')->once()->with(null)->andReturn($this->cacheRepository);
        $this->cacheRepository->shouldReceive('flushLocks')->once()->andReturn(false);
        $this->cacheRepository->shouldNotReceive('flush');

        $this->assertSame(1, $this->runCommand($this->command, ['--locks' => true]));
    }

    public function testProhibitWithoutArgumentsBlocksEveryStore()
    {
        ClearCommand::prohibit();

        $this->cacheManager->shouldNotReceive('store');
        $this->cacheRepository->shouldNotReceive('flush');

        $this->assertSame(1, $this->runCommand($this->command, ['store' => 'cache']));
    }

    public function testProhibitWithClosureCanBlockSpecificStores()
    {
        // Deny "cache:clear locks", but allow other stores through.
        ClearCommand::prohibit(fn ($input) => $input->getArgument('store') === 'locks');

        $this->cacheManager->shouldNotReceive('store');
        $this->cacheRepository->shouldNotReceive('flush');

        $this->assertSame(1, $this->runCommand($this->command, ['store' => 'locks']));
    }

    public function testProhibitWithClosureAllowsStoresThatAreNotBlocked()
    {
        // Same denylist closure as above: "cache:clear cache" must still run.
        ClearCommand::prohibit(fn ($input) => $input->getArgument('store') === 'locks');

        $this->files->shouldReceive('exists')->andReturn(true);
        $this->files->shouldReceive('files')->andReturn([]);

        $this->cacheManager->shouldReceive('store')->once()->with('cache')->andReturn($this->cacheRepository);
        $this->cacheRepository->shouldReceive('flush')->once()->andReturn(true);

        $this->assertSame(0, $this->runCommand($this->command, ['store' => 'cache']));
    }

    public function testProhibitWithAllowlistBlocksUnlistedStores()
    {
        // Allowlist: anything other than the "cache" store is prohibited. This
        // protects custom stores by default, including new ones added later
        // and the bare "cache:clear" (default store) invocation.
        ClearCommand::prohibit(fn ($input) => ! in_array($input->getArgument('store'), ['cache'], true));

        $this->cacheManager->shouldNotReceive('store');
        $this->cacheRepository->shouldNotReceive('flush');

        $this->assertSame(1, $this->runCommand($this->command, ['store' => 'locks']));
        $this->assertSame(1, $this->runCommand($this->command));
    }

    public function testProhibitWithAllowlistAllowsListedStores()
    {
        ClearCommand::prohibit(fn ($input) => ! in_array($input->getArgument('store'), ['cache'], true));

        $this->files->shouldReceive('exists')->andReturn(true);
        $this->files->shouldReceive('files')->andReturn([]);

        $this->cacheManager->shouldReceive('store')->once()->with('cache')->andReturn($this->cacheRepository);
        $this->cacheRepository->shouldReceive('flush')->once()->andReturn(true);

        $this->assertSame(0, $this->runCommand($this->command, ['store' => 'cache']));
    }

    public function testProhibitByStoreArgumentIgnoresTheLocksOption()
    {
        // A store *named* "locks" (the argument) is unrelated to the --locks
        // *option*, which clears lock entries from the default store. A closure
        // keyed on the store argument therefore does not block "cache:clear --locks".
        ClearCommand::prohibit(fn ($input) => $input->getArgument('store') === 'locks');

        $this->cacheManager->shouldReceive('store')->once()->with(null)->andReturn($this->cacheRepository);
        $this->cacheRepository->shouldReceive('flushLocks')->once()->andReturn(true);

        $this->assertSame(0, $this->runCommand($this->command, ['--locks' => true]));
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
