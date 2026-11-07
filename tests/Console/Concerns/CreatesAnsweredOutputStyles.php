<?php

namespace Illuminate\Tests\Console\Concerns;

use Illuminate\Console\OutputStyle;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

trait CreatesAnsweredOutputStyles
{
    /**
     * Build a real OutputStyle whose interactive input is pre-fed the given typed answer.
     */
    protected function outputStyleWithAnswer($answer)
    {
        $input = new ArrayInput([]);

        $stream = fopen('php://memory', 'w+');
        fwrite($stream, $answer."\n");
        rewind($stream);
        $input->setStream($stream);

        return new OutputStyle($input, new BufferedOutput);
    }
}
