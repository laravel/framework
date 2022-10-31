<?php

namespace Illuminate\Tests\Console;

use Illuminate\Console\Command;
use Illuminate\Console\CommandMutex;
use Illuminate\Container\Container;
use Illuminate\Contracts\Console\Isolatable;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class CommandMutexTest extends TestCase
{
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

        $container = Container::getInstance();
        $container->instance(CommandMutex::class, $this->commandMutex);
        $this->command->setLaravel($container);
    }

    public function testCanRunIsolatedCommandIfNotBlocked()
    {
        $this->commandMutex->shouldReceive('create')
            ->andReturn(true)
            ->once();
        $this->commandMutex->shouldReceive('forget')
            ->andReturn(true)
            ->once();

        $this->runCommand();

        $this->assertEquals(1, $this->command->ran);
    }

    public function testCannotRunIsolatedCommandIfBlocked()
    {
        $this->commandMutex->shouldReceive('create')
            ->andReturn(false)
            ->once();

        $this->runCommand();

        $this->assertEquals(0, $this->command->ran);
    }

    public function testCanRunCommandAgainAfterOtherCommandFinished()
    {
        $this->commandMutex->shouldReceive('create')
            ->andReturn(true)
            ->twice();
        $this->commandMutex->shouldReceive('forget')
            ->andReturn(true)
            ->twice();

        $this->runCommand();
        $this->runCommand();

        $this->assertEquals(2, $this->command->ran);
    }

    public function testCanRunCommandAgainNonAutomated()
    {
        $this->commandMutex->shouldNotHaveBeenCalled();

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
