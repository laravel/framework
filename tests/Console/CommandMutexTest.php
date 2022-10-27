<?php

declare(strict_types=1);

namespace Illuminate\Tests\Console;

use Illuminate\Console\Command;
use Illuminate\Console\CommandMutex;
use Illuminate\Container\Container;
use Illuminate\Contracts\Console\Isolated;
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
        $this->command = new class extends Command implements Isolated
        {
            public bool $ran = false;

            public function __invoke()
            {
                $this->ran = true;
            }
        };

        $this->commandMutex = m::mock(CommandMutex::class);

        $container = Container::getInstance();
        $container->instance(CommandMutex::class, $this->commandMutex);
        $this->command->setLaravel($container);
    }

    public function testCanRunIsolatedCommandIfNotBlocked()
    {
        $this->commandMutex->shouldReceive('create')->andReturn(true);

        $input = new ArrayInput([]);
        $output = new NullOutput;
        $this->command->run($input, $output);

        $this->assertTrue($this->command->ran);
    }

    public function testCannotRunIsolatedCommandIfBlocked()
    {
        $this->commandMutex->shouldReceive('create')->andReturn(false);

        $input = new ArrayInput([]);
        $output = new NullOutput;
        $this->command->run($input, $output);

        $this->assertFalse($this->command->ran);
    }
}
