<?php

namespace Illuminate\Tests\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Console\Kernel;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\Console\Attribute\AsCommand;

class KernelTest extends TestCase
{
    public function testFindCommandReturnsNullWhenTheCommandDoesNotExist()
    {
        $kernel = $this->makeKernel();

        $command = $kernel->findCommand('not-a-real-command');

        $this->assertNull($command);
    }

    public function testFindCommandRetrievesTheCommand()
    {
        $kernel = $this->makeKernel();
        $kernel->registerCommand(new KernelTestCommand);

        $command = $kernel->findCommand('kernel-test-command');

        $this->assertInstanceOf(KernelTestCommand::class, $command);
    }

    public function testFindCommandDoesNotResolveOtherLazilyRegisteredCommands()
    {
        KernelTestLazyCommand::$constructionAttempts = 0;
        $kernel = $this->makeKernel();
        $artisan = $this->getArtisan($kernel);
        $artisan->resolveCommands([KernelTestLazyCommand::class]);
        $artisan->setContainerCommandLoader();

        $kernel->registerCommand(new KernelTestCommand);

        $command = $kernel->findCommand('kernel-test-command');

        $this->assertInstanceOf(KernelTestCommand::class, $command);
        $this->assertSame(0, KernelTestLazyCommand::$constructionAttempts);

        $command = $kernel->findCommand('kernel-test-lazy-command');
        $this->assertSame(1, KernelTestLazyCommand::$constructionAttempts);
        $this->assertInstanceOf(KernelTestLazyCommand::class, $command);
    }

    public function testFindCommandReturnsTheSameInstanceOnSubsequentCalls()
    {
        KernelTestLazyCommand::$constructionAttempts = 0;
        $kernel = $this->makeKernel();
        $artisan = $this->getArtisan($kernel);
        $artisan->resolveCommands([KernelTestLazyCommand::class]);
        $artisan->setContainerCommandLoader();

        $first = $kernel->findCommand('kernel-test-lazy-command');
        $second = $kernel->findCommand('kernel-test-lazy-command');

        $this->assertSame($first, $second);
        $this->assertSame(1, KernelTestLazyCommand::$constructionAttempts);
    }

    protected function makeKernel(): Kernel
    {
        $app = new Application;
        $events = new Dispatcher($app);
        $app->instance('events', $events);

        return new Kernel($app, $events);
    }

    protected function getArtisan(Kernel $kernel)
    {
        $method = (new ReflectionClass($kernel))->getMethod('getArtisan');

        return $method->invoke($kernel);
    }
}

class KernelTestCommand extends Command
{
    protected $signature = 'kernel-test-command';

    public function handle()
    {
        //
    }
}

#[AsCommand(name: 'kernel-test-lazy-command')]
class KernelTestLazyCommand extends Command
{
    public static int $constructionAttempts = 0;

    public function __construct()
    {
        parent::__construct();

        static::$constructionAttempts++;
    }

    public function handle()
    {
        //
    }
}
