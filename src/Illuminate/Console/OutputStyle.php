<?php

namespace Illuminate\Console;

use Illuminate\Console\Contracts\NewLineAware;
use ReflectionClass;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class OutputStyle extends SymfonyStyle implements NewLineAware
{
    /**
     * If the last output wrote a new line.
     *
     * @var bool
     */
    protected $newLineWritten = false;

    /**
     * The output instance.
     *
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    private $output;

    /**
     * Create a new Console OutputStyle instance.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        parent::__construct($input, $output);

        with(new ReflectionClass(self::class))
            ->getParentClass()
            ->getProperty('questionHelper')
            ->setValue($this, new QuestionHelper());
    }

    /**
     * Returns whether verbosity is quiet (-q).
     *
     * @return bool
     */
    public function isQuiet(): bool
    {
        return $this->output->isQuiet();
    }

    /**
     * Returns whether verbosity is verbose (-v).
     *
     * @return bool
     */
    public function isVerbose(): bool
    {
        return $this->output->isVerbose();
    }

    /**
     * Returns whether verbosity is very verbose (-vv).
     *
     * @return bool
     */
    public function isVeryVerbose(): bool
    {
        return $this->output->isVeryVerbose();
    }

    /**
     * Returns whether verbosity is debug (-vvv).
     *
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->output->isDebug();
    }

    /**
     * Get the underlying Symfony output implementation.
     *
     * @return \Symfony\Component\Console\Output\OutputInterface
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * {@inheritdoc}
     */
    public function write(string|iterable $messages, bool $newline = false, int $options = 0)
    {
        $this->newLineWritten = $newline;

        parent::write($messages, $newline, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function writeln(string|iterable $messages, int $type = self::OUTPUT_NORMAL)
    {
        $this->newLineWritten = true;

        parent::writeln($messages, $type);
    }

    /**
     * {@inheritdoc}
     */
    public function newLine(int $count = 1)
    {
        $this->newLineWritten = $count > 0;

        parent::newLine($count);
    }

    /**
     * {@inheritdoc}
     */
    public function newLineWritten()
    {
        return $this->newLineWritten;
    }
}
