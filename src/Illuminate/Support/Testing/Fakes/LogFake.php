<?php

namespace Illuminate\Support\Testing\Fakes;

use Psr\Log\LoggerInterface;

class LogFake implements LoggerInterface
{
    /**
     * {@inheritdoc}
     */
    public function emergency($message, array $context = [])
    {
    }

    /**
     * {@inheritdoc}
     */
    public function alert($message, array $context = [])
    {
    }

    /**
     * {@inheritdoc}
     */
    public function critical($message, array $context = [])
    {
    }

    /**
     * {@inheritdoc}
     */
    public function error($message, array $context = [])
    {
    }

    /**
     * {@inheritdoc}
     */
    public function warning($message, array $context = [])
    {
    }

    /**
     * {@inheritdoc}
     */
    public function notice($message, array $context = [])
    {
    }

    /**
     * {@inheritdoc}
     */
    public function info($message, array $context = [])
    {
    }

    /**
     * {@inheritdoc}
     */
    public function debug($message, array $context = [])
    {
    }

    /**
     * {@inheritdoc}
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
