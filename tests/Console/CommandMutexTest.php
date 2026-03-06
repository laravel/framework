<?php

namespace Illuminate\Tests\Console;

use Illuminate\Console\Command;
use Illuminate\Console\CommandMutex;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
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

    /** {@inheritdoc} */
    #[\Override]
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

    /** {@inheritdoc} */
    #[\Override]
    protected function tearDown(): void
    {
        $this->tearDownTheTestEnvironmentUsingMockery();
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

    public function testReleasesIsolationMutexWhenCommandValidationFails()
    {
        $command = new class extends Command implements Isolatable
        {
            public $ran = 0;

            protected $signature = 'command:name {name?}';

            protected function rules(): array
            {
                return ['name' => 'required'];
            }

            public function __invoke()
            {
                $this->ran++;
            }
        };

        $command->setLaravel($this->command->getLaravel());

        $validator = m::mock(ValidatorContract::class);
        $validator->shouldReceive('fails')->once()->andReturn(true);
        $validator->shouldReceive('errors->all')->once()->andReturn(['The name field is required.']);

        $validationFactory = m::mock(ValidationFactory::class);
        $validationFactory->shouldReceive('make')->once()->andReturn($validator);

        $command->getLaravel()->instance(ValidationFactory::class, $validationFactory);

        $this->commandMutex->shouldReceive('create')
            ->andReturn(true)
            ->once();
        $this->commandMutex->shouldReceive('forget')
            ->andReturn(true)
            ->once();

        $input = new ArrayInput(['--isolated' => true]);
        $output = new NullOutput;

        $this->assertSame(Command::FAILURE, $command->run($input, $output));
        $this->assertSame(0, $command->ran);
    }

    protected function runCommand($withIsolated = true)
    {
        $input = new ArrayInput(['--isolated' => $withIsolated]);
        $output = new NullOutput;
        $this->command->run($input, $output);
    }
}
