<?php

namespace Illuminate\Tests\Console;

use Illuminate\Console\OutputStyle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

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
}
