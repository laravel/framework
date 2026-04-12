<?php

namespace Illuminate\Tests\Console;

use Illuminate\Console\OutputStyle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;

class OutputStyleTest extends TestCase
{
    public function testDetectsNewLine()
    {
        $bufferedOutput = new BufferedOutput();

        $style = new OutputStyle(new ArrayInput([]), $bufferedOutput);

        $this->assertFalse($style->newLineWritten());

        $style->newLine();
        $this->assertTrue($style->newLineWritten());
    }

    public function testDetectsNewLineOnUnderlyingOutput()
    {
        $bufferedOutput = new BufferedOutput();

        $underlyingStyle = new OutputStyle(new ArrayInput([]), $bufferedOutput);
        $style = new OutputStyle(new ArrayInput([]), $underlyingStyle);

        $underlyingStyle->newLine();
        $this->assertTrue($style->newLineWritten());
    }

    public function testDetectsNewLineOnWrite()
    {
        $bufferedOutput = new BufferedOutput();

        $style = new OutputStyle(new ArrayInput([]), $bufferedOutput);

        $style->write('Foo');
        $this->assertFalse($style->newLineWritten());

        $style->write('Foo', true);
        $this->assertTrue($style->newLineWritten());
    }

    public function testDetectsNewLineOnWriteln()
    {
        $bufferedOutput = new BufferedOutput();

        $style = new OutputStyle(new ArrayInput([]), $bufferedOutput);

        $style->writeln('Foo');
        $this->assertTrue($style->newLineWritten());
    }

    public function testDetectsNewLineOnlyOnOutput()
    {
        $bufferedOutput = new BufferedOutput();

        $style = new OutputStyle(new ArrayInput([]), $bufferedOutput);

        $style->setVerbosity(OutputStyle::VERBOSITY_NORMAL);

        $style->writeln('Foo', OutputStyle::VERBOSITY_VERBOSE);
        $this->assertFalse($style->newLineWritten());

        $style->setVerbosity(OutputStyle::VERBOSITY_VERBOSE);

        $style->writeln('Foo', OutputStyle::VERBOSITY_VERBOSE);
        $this->assertTrue($style->newLineWritten());
    }

    public function testErrorStyleReturnsSameInstanceWhenErrorOutputIsUnavailable()
    {
        $style = new OutputStyle(new ArrayInput([]), new BufferedOutput());

        $this->assertSame($style, $style->errorStyle());
    }

    public function testErrorStyleUsesErrorOutputWhenAvailable()
    {
        $output = new TestConsoleOutput;
        $style = new OutputStyle(new ArrayInput([]), $output);

        $errorStyle = $style->errorStyle();
        $errorStyle->writeln('Laravel');

        $this->assertSame('', $output->fetch());
        $this->assertStringContainsString('Laravel', $output->errorOutput()->fetch());
    }
}

class TestConsoleOutput extends BufferedOutput implements ConsoleOutputInterface
{
    /**
     * The error output instance.
     *
     * @var \Symfony\Component\Console\Output\BufferedOutput
     */
    protected $errorOutput;

    public function __construct()
    {
        parent::__construct();

        $this->errorOutput = new BufferedOutput();
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
        throw new \BadMethodCallException('Sections are not required for this test.');
    }

    public function errorOutput(): BufferedOutput
    {
        return $this->errorOutput;
    }
}
