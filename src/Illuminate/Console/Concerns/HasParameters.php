<?php

namespace Illuminate\Console\Concerns;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

trait HasParameters
{
    /**
     * Specify the arguments and options on the command.
     *
     * @return void
     */
    protected function specifyParameters()
    {
        // We will loop through all of the arguments and options for the command and
        // set them all on the base command instance. This specifies what can get
        // passed into these commands as "parameters" to control the execution.
        foreach ($this->getArguments() as $arguments) {
            if ($arguments instanceof InputArgument) {
                $this->getDefinition()->addArgument($arguments);
            } else {
                $this->addArgument(...$arguments);
            }
        }

        foreach ($this->getOptions() as $options) {
            if ($options instanceof InputOption) {
                $this->getDefinition()->addOption($options);
            } else {
                $this->addOption(...$options);
            }
        }
    }

    /**
     * Get the console command arguments.
     *
     * @return (\Symfony\Component\Console\Input\InputArgument|array{
     *    0: non-empty-string,
     *    1?: \Symfony\Component\Console\Input\InputArgument::REQUIRED|\Symfony\Component\Console\Input\InputArgument::OPTIONAL,
     *    2?: string,
     *    3?: mixed,
     *    4?: list<string|\Symfony\Component\Console\Completion\Suggestion>|\Closure(\Symfony\Component\Console\Completion\CompletionInput, \Symfony\Component\Console\Completion\CompletionSuggestions): list<string|\Symfony\Component\Console\Completion\Suggestion>
     * })[]
     */
    protected function getArguments()
    {
        return [];
    }

    /**
     * Get the console command options.
     *
     * @return (\Symfony\Component\Console\Input\InputOption|array{
     *    0: non-empty-string,
     *    1?: string|non-empty-array<string>,
     *    2?: \Symfony\Component\Console\Input\InputOption::VALUE_*,
     *    3?: string,
     *    4?: mixed,
     *    5?: list<string|\Symfony\Component\Console\Completion\Suggestion>|\Closure(\Symfony\Component\Console\Completion\CompletionInput, \Symfony\Component\Console\Completion\CompletionSuggestions): list<string|\Symfony\Component\Console\Completion\Suggestion>
     * })[]
     */
    protected function getOptions()
    {
        return [];
    }
}
