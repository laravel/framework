<?php

namespace Illuminate\Tests\Integration\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel;
use Orchestra\Testbench\TestCase;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleComponentErrorOutputTest extends TestCase
{
    public function testConsoleComponentsWriteToErrorOutputWhenAvailable(): void
    {
        $this->app[Kernel::class]->registerCommand(new class extends Command
        {
            protected $signature = 'test:console-components-output {--json}';

            public function handle(): int
            {
                $this->components->task('Doing work', fn () => true);

                if ($this->option('json')) {
                    $this->output->writeln(json_encode([['nice' => 'valid JSON!']], JSON_PRETTY_PRINT));
                }

                return self::SUCCESS;
            }
        });

        $output = new TestConsoleOutput;

        $this->app[Kernel::class]->handle(new StringInput('test:console-components-output --json'), $output);

        $standardOutput = $output->fetch();

        $this->assertJson($standardOutput);
        $this->assertJsonStringEqualsJsonString(
            json_encode([['nice' => 'valid JSON!']], JSON_PRETTY_PRINT),
            $standardOutput
        );

        $errorOutput = $output->errorOutput()->fetch();

        $this->assertStringContainsString('Doing work', $errorOutput);
        $this->assertStringContainsString('DONE', $errorOutput);
    }
}

class TestConsoleOutput extends BufferedOutput implements ConsoleOutputInterface
{
    /**
     * The error output instance.
     *
     * @var \Symfony\Component\Console\Output\BufferedOutput
     */
    protected $errorOutput;

    public function __construct()
    {
        parent::__construct();

        $this->errorOutput = new BufferedOutput();
    }

    public function getErrorOutput(): OutputInterface
    {
        return $this->errorOutput;
    }

    public function setErrorOutput(OutputInterface $error): void
    {
        $this->errorOutput = $error;
    }

    public function section(): ConsoleSectionOutput
    {
        throw new \BadMethodCallException('Sections are not required for this test.');
    }

    public function errorOutput(): BufferedOutput
    {
        return $this->errorOutput;
    }
}
