<?php

namespace Illuminate\Contracts\Log;

use Psr\Log\LoggerInterface;
use Monolog\Formatter\FormatterInterface;
use Illuminate\Contracts\Events\Dispatcher;

interface Channel extends LoggerInterface
{
    /**
     * Prepare the channel for logging.
     *
     * @param  array  $options
     * @return void
     */
    public function prepare(array $options = []);

    /**
     * Set the formatter to be used on the log channel.
     *
     * @param  \Monolog\Formatter\FormatterInterface  $formatter
     * @return void
     */
    public function setFormatter(FormatterInterface $formatter);

    /**
     * Setup an event listener for automatic logging.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @param  string  $event
     * @param  string  $level
     * @return void
     */
    public function listen(Dispatcher $events, $event, $level = 'debug');

    /**
     * Pass the channel through middleware for configuration.
     *
     * @param  array  $pipes
     * @return \Illuminate\Contracts\Log\Channel
     */
    public function through(array $pipes);

    /**
     * Retrieve the logger instance.
     *
     * @return LoggerInterface
     */
    public function getLogger();
}
