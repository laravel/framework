<?php

namespace Illuminate\Tests\Console;

use Illuminate\Console\Command;
use Illuminate\Console\CommandMutex;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Foundation\Application;
use Mockery as m;
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

        $this->commandMutex = m::mock(CommandMutex::class);

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
        $this->commandMutex->expects('create')
            ->andReturn(true);
        $this->commandMutex->expects('forget')
            ->andReturn(true);

        $this->runCommand();

        $this->assertEquals(1, $this->command->ran);
    }

    public function testCannotRunIsolatedCommandIfBlocked()
    {
        $this->commandMutex->expects('create')
            ->andReturn(false);

        $this->runCommand();

        $this->assertEquals(0, $this->command->ran);
    }

    public function testCanRunCommandAgainAfterOtherCommandFinished()
    {
        $this->commandMutex->expects('create')
            ->andReturn(true)
            ->times(2);
        $this->commandMutex->expects('forget')
            ->andReturn(true)
            ->times(2);

        $this->runCommand();
        $this->runCommand();

        $this->assertEquals(2, $this->command->ran);
    }

    public function testCanRunCommandAgainNonAutomated()
    {
        $this->runCommand(false);

        $this->commandMutex->shouldNotHaveReceived('create');
        $this->assertEquals(1, $this->command->ran);
    }

    protected function runCommand($withIsolated = true)
    {
        $input = new ArrayInput(['--isolated' => $withIsolated]);
        $output = new NullOutput;
        $this->command->run($input, $output);
    }
}
