<?php

namespace Illuminate\Foundation\Testing\Concerns;

use Illuminate\Console\OutputStyle;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Testing\PendingCommand;

trait InteractsWithConsole
{
    /**
     * Indicates if the console output should be mocked.
     *
     * @var bool
     */
    public $mockConsoleOutput = true;

    /**
     * Indicates if the command is expected to output anything.
     *
     * @var bool|null
     */
    public $expectsOutput;

    /**
     * All of the expected output lines.
     *
     * @var array
     */
    public $expectedOutput = [];

    /**
     * All of the expected text to be present in the output.
     *
     * @var array
     */
    public $expectedOutputSubstrings = [];

    /**
     * All of the output lines that aren't expected to be displayed.
     *
     * @var array
     */
    public $unexpectedOutput = [];

    /**
     * All of the text that is not expected to be present in the output.
     *
     * @var array
     */
    public $unexpectedOutputSubstrings = [];

    /**
     * All of the expected output tables.
     *
     * @var array
     */
    public $expectedTables = [];

    /**
     * All of the expected questions.
     *
     * @var array
     */
    public $expectedQuestions = [];

    /**
     * All of the expected choice questions.
     *
     * @var array
     */
    public $expectedChoices = [];

    /**
     * Invoke an Artisan command and return a mocked pending command or exit code.
     *
     * @param  string  $command
     * @param  array  $parameters
     * @return \Illuminate\Testing\PendingCommand|int
     */
    public function artisan($command, $parameters = [])
    {
        return $this->mockConsoleOutput
            ? $this->mockArtisan($command, $parameters)
            : $this->realArtisan($command, $parameters);
    }

    /**
     * Invoke an Artisan command and return a mocked pending command.
     *
     * @param  string  $command
     * @param  array  $parameters
     * @return \Illuminate\Testing\PendingCommand
     */
    public function mockArtisan($command, $parameters = []): PendingCommand
    {
        return new PendingCommand($this, $this->app, $command, $parameters);
    }

    /**
     * Invoke an Artisan command and return the exit code.
     *
     * @param  string  $command
     * @param  array  $parameters
     * @return int
     */
    public function realArtisan($command, $parameters = []): int
    {
        return $this->app[Kernel::class]->call($command, $parameters);
    }

    /**
     * Disable mocking the console output.
     *
     * @return $this
     */
    protected function withoutMockingConsoleOutput()
    {
        $this->mockConsoleOutput = false;

        $this->app->offsetUnset(OutputStyle::class);

        return $this;
    }
}
