<?php

namespace Illuminate\Log;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Log\Channel;
use Illuminate\Contracts\Events\Dispatcher;

class LogManager extends AbstractLogger implements LoggerInterface
{
    /**
     * The available log channels.
     *
     * @var \Illuminate\Contracts\Log\Channel[]
     */
    protected $channels = [];

    /**
     * The default log channels.
     *
     * @var string[]
     */
    protected $defaultChannels = [];

    /**
     * The events dispatcher.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * Create the LogManager.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     */
    public function __construct(Dispatcher $events)
    {
        $this->events = $events;
    }

    /**
     * Retrieve a channel to use for logging.
     *
     * @param  string  $channel
     * @return \Illuminate\Contracts\Log\Channel
     */
    public function to($channel)
    {
        return $this->resolveChannel($channel);
    }

    /**
     * Attach an event listener to log a certain event.
     *
     * @param  string|array  $event
     * @param  string  $level
     * @param  null|array|string  $channels
     * @return \Illuminate\Log\EventLogger|\Illuminate\Contracts\Log\Channel
     */
    public function event($event, $level = 'debug', $channels = null)
    {
        $logger = new EventLogger($this, $this->events, $event, $level);

        if (! $channels) {
            return $logger;
        }

        return $logger->to($channels);
    }

    /**
     * Resolve a channel by name.
     *
     * @param  string  $channel
     * @return \Illuminate\Contracts\Log\Channel
     *
     * @throws \Illuminate\Log\ChannelResolutionException
     */
    protected function resolveChannel($channel)
    {
        if (! array_key_exists($channel, $this->channels)) {
            throw new ChannelResolutionException("The log channel $channel is not registered.");
        }

        if ($this->channels[$channel] instanceof Channel) {
            return $this->channels[$channel];
        }

        return $this->resolveChannel($this->channels[$channel]);
    }

    /**
     * Register a new log channel.
     *
     * @param  string  $name
     * @param  \Illuminate\Contracts\Log\Channel  $channel
     * @return void
     */
    public function registerChannel($name, Channel $channel)
    {
        $this->channels[$name] = $channel;
    }

    /**
     * Set the default channels to which all calls are directed by default.
     *
     * @param  array  $channels
     * @return void
     */
    public function setDefaultChannels(array $channels)
    {
        $this->defaultChannels = $channels;
    }

    /**
     * Proxy a method call to the default channels.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return void
     */
    public function proxyCall($method, array $parameters)
    {
        foreach ($this->defaultChannels as $channel) {
            $this->to($channel)->{$method}(...$parameters);
        }
    }

    /**
     * Add a log record at an arbitrary level.
     *
     * @param  mixed  $level
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function log($level, $message, array $context = [])
    {
        $this->proxyCall($level, [$message, $context]);
    }

    /**
     * Call a method on all default channels.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, array $parameters)
    {
        return $this->proxyCall($method, $parameters);
    }
}
