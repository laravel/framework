<?php

namespace Illuminate\Support\Testing\Fakes;

class LogChannelFake
{
    protected $name;

    protected $logger;

    public function __construct($logger, $name = null)
    {
        $this->logger = $logger;

        $this->name = $name;
    }

    public function __call($method, $arguments)
    {
        $this->logger->setCurrentChannel($this->name);

        $this->logger->{$method}(...$arguments);

        $this->logger->setCurrentChannel(null);
    }
}
