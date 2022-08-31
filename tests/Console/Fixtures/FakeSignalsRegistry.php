<?php

namespace Illuminate\Tests\Console\Fixtures;

class FakeSignalsRegistry
{
    protected $signalHandlers = [
        SIGINT => [],
    ];

    public function register($signal, $signalHandler)
    {
        $this->signalHandlers[$signal][] = $signalHandler;
    }

    public function handle($signal)
    {
        $count = count($this->signalHandlers[$signal]);

        foreach ($this->signalHandlers[$signal] as $i => $signalHandler) {
            $hasNext = $i !== $count - 1;
            $signalHandler($signal, $hasNext);
        }
    }
}
