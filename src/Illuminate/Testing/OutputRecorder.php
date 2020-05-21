<?php

namespace Illuminate\Testing;

use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;

class OutputRecorder extends Output
{
    /**
     * The output target to be recorded.
     *
     * @var OutputInterface
     */
    protected $target;

    /**
     * The recorded output.
     *
     * @var string
     */
    protected $recording = '';

    /**
     * @param OutputInterface               $target The output target to record
     * @param int                           $verbosity The verbosity level (one of the VERBOSITY constants in OutputInterface)
     * @param bool                          $decorated Whether to decorate messages
     * @param OutputFormatterInterface|null $formatter Output formatter target (null to use default OutputFormatter)
     */
    public function __construct(
        OutputInterface $target,
        ?int $verbosity = self::VERBOSITY_NORMAL,
        bool $decorated = false,
        OutputFormatterInterface $formatter = null
    ) {
        $this->target = $target;

        parent::__construct($verbosity, $decorated, $formatter);
    }

    /**
     * Get the underlying output target.
     *
     * @return OutputInterface
     */
    public function target()
    {
        return $this->target;
    }

    /**
     * Get the recorded output.
     *
     * @return string
     */
    public function getRecording()
    {
        return $this->recording;
    }

    /**
     * {@inheritdoc}
     */
    protected function doWrite(string $message, bool $newline)
    {
        $this->recording .= $message;

        if ($newline) {
            $this->recording .= PHP_EOL;
        }

        $this->target->doWrite($message, $newline);
    }
}
