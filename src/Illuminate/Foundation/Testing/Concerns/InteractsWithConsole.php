<?php

namespace Illuminate\Foundation\Testing\Concerns;

use Illuminate\Foundation\Testing\PendingCommand;

trait InteractsWithConsole
{
    /**
     * All of the expected output lines.
     *
     * @var array
     */
    public $expectedOutput = [];

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
     * @return \Illuminate\Foundation\Testing\PendingCommand
     */
    public function artisan($command, $parameters = [])
    {
        $this->beforeApplicationDestroyed(function () {
            if (count($this->expectedQuestions)) {
                $this->fail('Question "'.array_first($this->expectedQuestions)[0].'" was not asked.');
            }

            if (count($this->expectedOutput)) {
                $this->fail('Output "'.array_first($this->expectedOutput).'" was not printed.');
            }
        });

        return new PendingCommand($this, $this->app, $command, $parameters);
    }
}
