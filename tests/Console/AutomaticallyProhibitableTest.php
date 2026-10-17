<?php

namespace Illuminate\Tests\Console;

use Illuminate\Console\AutomaticallyProhibitable;
use Illuminate\Console\Command;
use Illuminate\Console\CommandMutex;
use Illuminate\Console\Prohibitable;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Foundation\Application;
use Mockery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class AutomaticallyProhibitableTest extends TestCase
{
    protected function tearDown(): void
    {
        AutomaticallyProhibitableCommand::prohibit(false);
        AutomaticallyProhibitableParentCommand::prohibit(false);
        AutomaticallyProhibitableIsolatableCommand::prohibit(false);
        ManuallyProhibitableCommand::prohibit(false);

        Mockery::close();

        parent::tearDown();
    }

    public function testProhibitedCommandDoesNotRunHandle()
    {
        AutomaticallyProhibitableCommand::prohibit();

        $command = new AutomaticallyProhibitableCommand;
        $output = new BufferedOutput;

        $exitCode = $this->runCommand($command, $output);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertFalse($command->handled);
        $this->assertStringContainsString('This command is prohibited from running in this environment.', $output->fetch());
    }

    public function testCommandRunsWhenNotProhibited()
    {
        AutomaticallyProhibitableCommand::prohibit(false);

        $command = new AutomaticallyProhibitableCommand;

        $exitCode = $this->runCommand($command);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertTrue($command->handled);
    }

    public function testTraitIsDetectedOnParentClass()
    {
        AutomaticallyProhibitableChildCommand::prohibit();

        $command = new AutomaticallyProhibitableChildCommand;

        $exitCode = $this->runCommand($command);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertFalse($command->handled);
    }

    public function testProhibitableCommandIsNotCheckedAutomatically()
    {
        ManuallyProhibitableCommand::prohibit();

        $command = new ManuallyProhibitableCommand;

        $exitCode = $this->runCommand($command);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertTrue($command->handled);
    }

    public function testProhibitedIsolatableCommandDoesNotAcquireMutex()
    {
        AutomaticallyProhibitableIsolatableCommand::prohibit();

        $mutex = Mockery::mock(CommandMutex::class);
        $mutex->shouldNotReceive('create');
        $mutex->shouldNotReceive('forget');

        $command = new AutomaticallyProhibitableIsolatableCommand;

        $exitCode = $this->runCommand($command, input: ['--isolated' => true], mutex: $mutex);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertFalse($command->handled);
    }

    protected function runCommand(Command $command, ?BufferedOutput $output = null, array $input = [], ?CommandMutex $mutex = null): int
    {
        $application = new Application(__DIR__);

        if ($mutex) {
            $application->instance(CommandMutex::class, $mutex);
        }

        $command->setLaravel($application);

        return $command->run(new ArrayInput($input), $output ?? new BufferedOutput);
    }
}

class AutomaticallyProhibitableCommand extends Command
{
    use AutomaticallyProhibitable;

    protected $signature = 'prohibitable:automatic';

    public $handled = false;

    public function handle()
    {
        $this->handled = true;
    }
}

abstract class AutomaticallyProhibitableParentCommand extends Command
{
    use AutomaticallyProhibitable;

    public $handled = false;

    public function handle()
    {
        $this->handled = true;
    }
}

class AutomaticallyProhibitableChildCommand extends AutomaticallyProhibitableParentCommand
{
    protected $signature = 'prohibitable:child';
}

class AutomaticallyProhibitableIsolatableCommand extends Command implements Isolatable
{
    use AutomaticallyProhibitable;

    protected $signature = 'prohibitable:isolatable';

    public $handled = false;

    public function handle()
    {
        $this->handled = true;
    }
}

class ManuallyProhibitableCommand extends Command
{
    use Prohibitable;

    protected $signature = 'prohibitable:manual';

    public $handled = false;

    public function handle()
    {
        $this->handled = true;
    }
}
