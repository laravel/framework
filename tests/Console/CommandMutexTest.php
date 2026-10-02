<?php

namespace Illuminate\Tests\Console;

use Illuminate\Cache\Repository;
use Illuminate\Cache\StorageStore;
use Illuminate\Console\CacheCommandMutex;
use Illuminate\Console\Command;
use Illuminate\Console\CommandMutex;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Foundation\Application;
use Illuminate\Tests\Cache\Fixtures\ArrayFilesystem;
use Illuminate\Tests\Console\Fixtures\FakeCacheFactory;
use Orchestra\Testbench\Concerns\InteractsWithMockery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class CommandMutexTest extends TestCase
{
    use InteractsWithMockery;

    /**
     * @var Command
     */
    protected $command;

    /**
     * @var CommandMutex
     */
    protected $commandMutex;

    protected function setUp(): void
    {
        $this->command = new class extends Command implements Isolatable
        {
            public $ran = 0;

            public function __invoke()
            {
                $this->ran++;
            }
        };

        $this->commandMutex = new CacheCommandMutex(new FakeCacheFactory(
            new Repository(new StorageStore(new ArrayFilesystem, 'cache'))
        ));

        $app = new Application;
        $app->instance(CommandMutex::class, $this->commandMutex);
        $this->command->setLaravel($app);
    }

    protected function tearDown(): void
    {
        $this->tearDownTheTestEnvironmentUsingMockery();
    }

    public function testCanRunIsolatedCommandIfNotBlocked()
    {
        $this->runCommand();

        $this->assertEquals(1, $this->command->ran);
    }

    public function testCannotRunIsolatedCommandIfBlocked()
    {
        $this->commandMutex->create($this->command);

        $this->runCommand();

        $this->assertEquals(0, $this->command->ran);
    }

    public function testCanRunCommandAgainAfterOtherCommandFinished()
    {
        $this->runCommand();
        $this->runCommand();

        $this->assertEquals(2, $this->command->ran);
    }

    public function testCanRunCommandAgainNonAutomated()
    {
        $this->commandMutex->create($this->command);

        $this->runCommand(false);

        $this->assertEquals(1, $this->command->ran);
    }

    protected function runCommand($withIsolated = true)
    {
        $input = new ArrayInput(['--isolated' => $withIsolated]);
        $output = new NullOutput;
        $this->command->run($input, $output);
    }
}
