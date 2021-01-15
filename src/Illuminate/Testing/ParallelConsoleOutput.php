<?php

namespace Illuminate\Testing;

use Illuminate\Support\Str;
use Symfony\Component\Console\Output\ConsoleOutput;

class ParallelConsoleOutput extends ConsoleOutput
{
    /**
     * The original output instance.
     *
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected $output;

    /**
     * The output that should be ignored.
     *
     * @var array
     */
    protected $ignore = [
        'Running phpunit in',
        'Configuration read from',
    ];

    /**
     * Create a new Parallel ConsoleOutput instance.
     *
     * @param  \Symfony\Component\Console\Output\OutputInterface
     * @return void
     */
    public function __construct($output)
    {
        parent::__construct(
            $output->getVerbosity(),
            $output->isDecorated(),
            $output->getFormatter(),
        );

        $this->output = $output;
    }

    /**
     * Writes a message to the output.
     *
     * @param  string|iterable  $messages
     * @param  bool  $newline
     * @param  int  $options
     * @return void
     */
    public function write($messages, bool $newline = false, int $options = 0)
    {
        $messages = collect($messages)->filter(function ($message) {
            return ! Str::contains($message, $this->ignore);
        });

        $this->output->write($messages->toArray(), $newline, $options);
    }
}
