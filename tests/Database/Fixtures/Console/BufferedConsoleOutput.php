<?php

namespace Illuminate\Tests\Database\Fixtures\Console;

use LogicException;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;

class BufferedConsoleOutput extends BufferedOutput implements ConsoleOutputInterface
{
    protected OutputInterface $errorOutput;

    public function __construct()
    {
        parent::__construct();

        $this->errorOutput = new BufferedOutput;
    }

    public function getErrorOutput(): OutputInterface
    {
        return $this->errorOutput;
    }

    public function setErrorOutput(OutputInterface $error): void
    {
        $this->errorOutput = $error;
    }

    public function section(): ConsoleSectionOutput
    {
        throw new LogicException('Sections are not supported.');
    }

    public function fetchErrors(): string
    {
        return $this->errorOutput->fetch();
    }
}
