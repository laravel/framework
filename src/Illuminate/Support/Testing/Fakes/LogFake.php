<?php

namespace Illuminate\Support\Testing\Fakes;

use Psr\Log\LoggerInterface;

class LogFake implements LoggerInterface
{
    /**
     * @inheritDoc
     */
    public function emergency($message, array $context = [])
    {
    }

    /**
     * @inheritDoc
     */
    public function alert($message, array $context = [])
    {
    }

    /**
     * @inheritDoc
     */
    public function critical($message, array $context = [])
    {
    }

    /**
     * @inheritDoc
     */
    public function error($message, array $context = [])
    {
    }

    /**
     * @inheritDoc
     */
    public function warning($message, array $context = [])
    {
    }

    /**
     * @inheritDoc
     */
    public function notice($message, array $context = [])
    {
    }

    /**
     * @inheritDoc
     */
    public function info($message, array $context = [])
    {
    }

    /**
     * @inheritDoc
     */
    public function debug($message, array $context = [])
    {
    }

    /**
     * @inheritDoc
     */
    public function log($level, $message, array $context = [])
    {
    }

    /**
     * @param  string|null  $channel
     * @return \Illuminate\Support\Testing\Fakes\LogFake
     */
    public function channel($channel = null)
    {
        return $this;
    }

    /**
     * @param  array  $channels
     * @param  string|null  $channel
     * @return \Illuminate\Support\Testing\Fakes\LogFake
     */
    public function stack(array $channels, $channel = null)
    {
        return $this;
    }
}
