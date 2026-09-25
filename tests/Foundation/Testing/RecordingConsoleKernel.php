<?php

namespace Illuminate\Tests\Foundation\Testing;

use Illuminate\Contracts\Console\Kernel;

class RecordingConsoleKernel implements Kernel
{
    public array $calls = [];

    public function bootstrap()
    {
        //
    }

    public function handle($input, $output = null)
    {
        return 0;
    }

    public function call($command, array $parameters = [], $outputBuffer = null)
    {
        $this->calls[] = [$command, $parameters];

        return 0;
    }

    public function queue($command, array $parameters = [])
    {
        //
    }

    public function all()
    {
        return [];
    }

    public function output()
    {
        return '';
    }

    public function terminate($input, $status)
    {
        //
    }

    public function setArtisan($artisan)
    {
        //
    }
}
