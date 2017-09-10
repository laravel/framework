<?php

namespace Illuminate\Log;

use Monolog\Logger;
use ReflectionProperty;
use Psr\Log\AbstractLogger;
use Illuminate\Pipeline\Pipeline;
use Monolog\Formatter\FormatterInterface;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Log\Channel as ChannelContract;

abstract class Channel extends AbstractLogger implements ChannelContract
{
    /**
     * The Monolog instance.
     *
     * @var \Monolog\Logger
     */
    protected $writer;

    /**
     * The Laravel Illuminate container.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $app;

    /**
     * Create the channel instance.
     *
     * @param  \Illuminate\Contracts\Container\Container  $app
     * @param  \Monolog\Logger  $writer
     * @return void
     */
    public function __construct(Container $app, Logger $writer)
    {
        $this->writer = $writer;
        $this->app = $app;
    }

    /**
     * Set the formatter to be used on the log channel.
     *
     * @param  \Monolog\Formatter\FormatterInterface  $formatter
     * @return void
     */
    public function setFormatter(FormatterInterface $formatter)
    {
        foreach ($this->writer->getHandlers() as $handler) {
            $handler->setFormatter($formatter);
        }
    }

    /**
     * Setup an event listener for automatic logging.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @param  string  $event
     * @param  string  $level
     * @return void
     */
    public function listen(Dispatcher $events, $event, $level = 'debug')
    {
        $events->listen($event, function (...$data) use ($event, $level) {
            if (! empty($data) && is_object($data[0])) {
                $reflection = new \ReflectionClass($data[0]);
                $data = collect($reflection->getProperties(ReflectionProperty::IS_PUBLIC))->flatMap(function (ReflectionProperty $property) use ($data) {
                    return [$property->name => $property->getValue($data[0])];
                })->all();
            }

            $this->log($level, "Event triggered: $event", ['event_data' => $data]);
        });
    }

    /**
     * Add a log record at an arbitrary level.
     *
     * @param  mixed  $level
     * @param  string  $message
     * @param  array  $context
     * @return mixed
     */
    public function log($level, $message, array $context = [])
    {
        $this->writer->log($level, $message, $context);
    }

    /**
     * Pass the channel through middleware for configuration.
     *
     * @param  array  $pipes
     * @return \Illuminate\Contracts\Log\Channel
     */
    public function through(array $pipes)
    {
        return (new Pipeline($this->app))
            ->send($this)
            ->through($pipes)
            ->then(function ($channel) {
                return $channel;
            });
    }

    /**
     * Retrieve the logger instance.
     *
     * @return \Monolog\Logger
     */
    public function getLogger()
    {
        return $this->writer;
    }
}
