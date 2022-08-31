<?php

namespace Illuminate\Console;

/**
 * @internal
 */
class Signals
{
    /**
     * The signal registry instance.
     *
     * @var \Symfony\Component\Console\SignalRegistry\SignalRegistry
     */
    protected $registry;

    /**
     * The signal registry's previous list of handlers.
     *
     * @param array<int, array<int, callable>>|null
     */
    protected $previousHandlers;

    /**
     * The current availability resolver, if any.
     *
     * @param (callable(): bool)|null
     */
    protected static $availabilityResolver;

    /**
     * Create a new Signals instance.
     *
     * @param  \Symfony\Component\Console\SignalRegistry\SignalRegistry  $registry
     * @return void
     */
    public function __construct($registry)
    {
        $this->registry = $registry;

        $this->previousHandlers = $this->getHandlers();
    }

    /**
     * Registers a new signal handler.
     *
     * @param  int  $signal
     * @param  callable  $callback
     * @return void
     */
    public function register($signal, $callback)
    {
        $this->previousHandlers[$signal] ??= $this->initializeSignal($signal);

        with($this->getHandlers(), function ($handlers) use ($signal) {
            $handlers[$signal] ??= $this->initializeSignal($signal);

            $this->setHandlers($handlers);
        });

        $this->registry->register($signal, $callback);

        with($this->getHandlers(), function ($handlers) use ($signal) {
            $lastHandlerInserted = array_pop($handlers[$signal]);
            array_unshift($handlers[$signal], $lastHandlerInserted);

            $this->setHandlers($handlers);
        });
    }

    /**
     * Unregister the current signals instance, and reverts
     * the signal's registry handlers state.
     *
     * @return array<int, array<int, callable>>
     */
    public function unregister()
    {
        $this->setHandlers($this->previousHandlers);
    }

    /**
     * Sets the availability resolver.
     *
     * @param  callable(): bool
     * @return void
     */
    public static function resolveAvailabilityUsing($resolver)
    {
        static::$availabilityResolver = $resolver;
    }

    /**
     * Executes the given callback if "signals" should be used and are available.
     *
     * @param  callable  $callback
     * @return void
     */
    public static function whenAvailable($callback)
    {
        $resolver = static::$availabilityResolver
            ?? fn () => (app()->runningInConsole()
                && ! app()->runningUnitTests()
                && extension_loaded('pcntl')
                && extension_loaded('posix'));

        if ($resolver()) {
            $callback();
        }
    }

    /**
     * Set the registry's handlers.
     *
     * @param  array<int, array<int, callable>>  $handlers
     * @return void
     */
    protected function setHandlers($handlers)
    {
        (fn () => $this->signalHandlers = $handlers)
            ->call($this->registry);
    }

    /**
     * Get the registry's handlers.
     *
     * @return array<int, array<int, callable>>
     */
    protected function getHandlers()
    {
        return (fn () => $this->signalHandlers)
            ->call($this->registry);
    }

    /**
     * Gets the signal's existing handler, and returns it on the array format.
     *
     * @return array<int, callable(int $signal): mixed>
     */
    protected function initializeSignal($signal)
    {
        return is_callable($existingHandler = pcntl_signal_get_handler($signal))
            ? [$existingHandler]
            : [function () use ($signal) {
                pcntl_signal($signal, SIG_DFL);
                posix_kill(posix_getpid(), $signal);
            }];
    }
}
