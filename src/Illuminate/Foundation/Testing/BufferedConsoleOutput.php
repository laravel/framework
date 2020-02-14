<?php

namespace Illuminate\Foundation\Testing;

use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\Output;

class BufferedConsoleOutput extends Output
{
    protected static $buffer = '';

    /**
     * Empties buffer and returns its content.
     *
     * @return string
     */
    public function fetch()
    {
        $content = self::$buffer;
        self::$buffer = '';
        MockStream::restore();

        return $content;
    }

    /**
     * {@inheritdoc}
     */
    public function doWrite($message, $newline)
    {
        self::$buffer .= $message;

        if ($newline) {
            self::$buffer .= PHP_EOL;
        }
    }

    /**
     * Creates a new output section.
     */
    public function section()
    {
        MockStream::register($this);
        $sections = [];
        return new ConsoleSectionOutput(MockStream::getStream(), $sections, $this->getVerbosity(), $this->isDecorated(), $this->getFormatter());
    }
}
