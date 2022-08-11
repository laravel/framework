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
     * All the expected output lines.
     *
     * @var array
     */
    public $expectedOutput = [];

    /**
     * All the expected text to be present in the output.
     *
     * @var array
     */
    public $expectedOutputSubstrings = [];

    /**
     * All the output lines that aren't expected to be displayed.
     *
     * @var array
     */
    public $unexpectedOutput = [];

    /**
     * All the text that is not expected to be present in the output.
     *
     * @var array
     */
    public $unexpectedOutputSubstrings = [];

    /**
     * All the expected output tables.
     *
     * @var array
     */
    public $expectedTables = [];

    /**
     * All the expected questions.
     *
     * @var array
     */
    public $expectedQuestions = [];

    /**
     * All the expected choice questions.
     *
     * @var array
     */
    public $expectedChoices = [];

    /**
     * Call artisan command and return code.
     *
     * @param  string  $command
     * @param  array  $parameters
     * @return \Illuminate\Testing\PendingCommand|int
     */
    public function artisan($command, $parameters = [])
    {
        if (! $this->mockConsoleOutput) {
            return $this->app[Kernel::class]->call($command, $parameters);
        }

        return new PendingCommand($this, $this->app, $command, $parameters);
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
