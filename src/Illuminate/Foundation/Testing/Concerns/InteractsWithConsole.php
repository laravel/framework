<?php

namespace Illuminate\Foundation\Testing\Concerns;

use Illuminate\Console\OutputStyle;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\PendingCommand;
use Illuminate\Support\Arr;

trait InteractsWithConsole
{
    /**
     * Indicates if the console output should be mocked.
     *
     * @var bool
     */
    public $mockConsoleOutput = true;

    /**
     * All of the expected output lines.
     *
     * @var array
     */
    public $expectedOutput = [];

    /**
     * All of the actual output lines.
     *
     * @var array
     */
    public $actualOutput = [];

    /**
     * All of the expected questions.
     *
     * @var array
     */
    public $expectedQuestions = [];

    /**
     * Call artisan command and return code.
     *
     * @param  string  $command
     * @param  array  $parameters
     * @return \Illuminate\Foundation\Testing\PendingCommand|int
     */
    public function artisan($command, $parameters = [])
    {
        if (! $this->mockConsoleOutput) {
            return $this->app[Kernel::class]->call($command, $parameters);
        }

        $this->beforeApplicationDestroyed(function () {
            if (count($this->expectedQuestions)) {
                $this->fail('Question "'.Arr::first($this->expectedQuestions)[0].'" was not asked.');
            }

            $assertedOutput = collect($this->actualOutput)->diff(collect($this->actualOutput)->diff($this->expectedOutput));
            if ($assertedOutput->values()->toArray() !== $this->expectedOutput) {
                $missingOutput = collect($this->expectedOutput)->diff($this->actualOutput)->unique();
                $missingString = $missingOutput->isEmpty()
                    ? "Output given in wrong order:\n" . collect($this->expectedOutput)->join("\n")
                    : "Expected output missing:\n" . $missingOutput->join("\n");
                $actualString = count($this->actualOutput) === 0 ? 'No output was given.' : "Actual output was:\n" . join("\n", $this->actualOutput);

                $this->fail("{$missingString}\n\n{$actualString}");
            }
        });

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
