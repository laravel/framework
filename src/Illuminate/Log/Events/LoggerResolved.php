<?php

namespace Illuminate\Log\Events;

use Psr\Log\LoggerInterface;

class LoggerResolved
{
    /**
     * Logger.
     *
     * @var \Psr\Log\LoggerInterface
     */
    public $logger;

    /**
     * Create a new event instance.
     *
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
}
