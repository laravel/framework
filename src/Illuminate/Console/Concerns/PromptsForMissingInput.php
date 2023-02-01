<?php

namespace Illuminate\Console\Concerns;

use Illuminate\Contracts\Console\PromptsForMissingInput as PromptsForMissingInputContract;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

trait PromptsForMissingInput
{
    /**
     * Interact with the user before validating the input.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        parent::interact($input, $output);

        if ($this instanceof PromptsForMissingInputContract) {
            $this->promptForMissingArguments($input, $output);
        }
    }

    /**
     * Prompt the user for any missing arguments.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    protected function promptForMissingArguments(InputInterface $input, OutputInterface $output)
    {
        $prompted = collect($this->getDefinition()->getArguments())
            ->filter(fn ($argument) => $argument->isRequired() && is_null($input->getArgument($argument->getName())))
            ->filter(fn ($argument) => $argument->getName() !== 'command')
            ->each(fn ($argument) => $input->setArgument(
                $argument->getName(),
                $this->askPersistently(
                    $this->promptForMissingArgumentsUsing()[$argument->getName()] ??
                    'What is '.lcfirst($argument->getDescription()).'?'
                )
            ))
            ->isNotEmpty();

        if ($prompted) {
            $this->afterPromptingForMissingArguments($input, $output);
        }
    }

    /**
     * Prompt for missing input arguments using the returned questions.
     *
     * @return array
     */
    protected function promptForMissingArgumentsUsing()
    {
        return [];
    }

    /**
     * Perform actions after the user was prompted for missing arguments.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    protected function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output)
    {
        //
    }

    /**
     * Whether the input contains any options that differ from the default values.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @return bool
     */
    protected function didReceiveOptions(InputInterface $input)
    {
        return collect($this->getDefinition()->getOptions())
            ->reject(fn ($option) => $input->getOption($option->getName()) === $option->getDefault())
            ->isNotEmpty();
    }

    /**
     * Continue asking a question until an answer is provided.
     *
     * @param  string  $question
     * @return string
     */
    private function askPersistently($question)
    {
        $answer = null;

        while ($answer === null) {
            $answer = $this->components->ask($question);

            if ($answer === null) {
                $this->components->error('The answer is required.');
            }
        }

        return $answer;
    }
}
