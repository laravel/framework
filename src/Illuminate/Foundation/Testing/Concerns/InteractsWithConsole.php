<?php

namespace Illuminate\Foundation\Testing\Concerns;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\PendingCommand;

trait InteractsWithConsole
{
    /**
     * The list of expected questions with their answers.
     * 
     * @var array
     */
    public $expectedQuestions = [];

    /**
     * The list of expected outputs.
     * 
     * @var array
     */
    public $expectedOutput = [];

    /**
     * Call artisan command and return code.
     *
     * @param  string  $command
     * @param  array  $parameters
     * @return int
     */
    public function artisan($command, $parameters = [])
    {
        $this->beforeApplicationDestroyed(function () {
            if (count($this->expectedQuestions)) {
                $this->fail('Question "'.array_first($this->expectedQuestions)[0].'" was never asked!');
            }

            if (count($this->expectedOutput)) {
                $this->fail('Output "'.array_first($this->expectedOutput).'" was never printed!');
            }
        });

        return new PendingCommand($this, $this->app, $command, $parameters);
    }
}
