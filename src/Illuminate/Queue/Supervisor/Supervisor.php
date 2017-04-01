<?php

namespace Illuminate\Queue\Supervisor;

use Illuminate\Queue\Supervisor\Events\LoopCompleting;
use Illuminate\Queue\Supervisor\Events\LoopBeginning;
use Illuminate\Queue\Supervisor\Events\RunFailed;
use Illuminate\Queue\Supervisor\Events\RunSucceed;
use Illuminate\Queue\Supervisor\Events\SupervisorStopping;
use Illuminate\Contracts\Bus\Dispatcher as Bus;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher as Events;
use Illuminate\Contracts\Foundation\Application;
use Closure;
use Symfony\Component\Debug\Exception\FatalThrowableError;

class Supervisor
{
    /**
     * @var Application
     */
    protected $laravel;

    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @var Bus
     */
    protected $bus;

    /**
     * @var Events
     */
    protected $events;

    /**
     * @var ExceptionHandler
     */
    protected $exceptions;

    /**
     * @param Application $laravel
     * @param Cache $cache
     * @param Bus $bus
     * @param Events $events
     * @param ExceptionHandler $exceptions
     */
    public function __construct(Application $laravel, Cache $cache, Bus $bus, Events $events, ExceptionHandler $exceptions)
    {
        $this->laravel = $laravel;
        $this->cache = $cache;
        $this->bus = $bus;
        $this->events = $events;
        $this->exceptions = $exceptions;
    }

    /**
     * Run and monitor a command in a loop.
     *
     * @param Closure|string|object $command
     * @param SupervisorOptions|null $options
     */
    public function supervise($command, SupervisorOptions $options = null)
    {
        $command = $this->resolveCommand($command);

        $options = $options ? : new SupervisorOptions();
        /** @var SupervisorState $state */
        $state = $this->laravel->make(SupervisorState::class);

        $this->listenForSignals($state);

        $state->lastRestart = $this->getTimestampOfLastRestart();

        while (true) {
            $this->registerTimeoutHandler($options);

            // Before running any command, we will make sure this supervisor is not paused and
            // if it is we will just pause for a given amount of time and
            // make sure we do not need to kill this process off completely.
            if (! $this->shouldRun($options, $state)) {
                $this->pause();
            } else {
                $this->run($command, $options, $state);
            }

            // Finally, we will check to see if we have exceeded our memory limits or if
            // this process should restart based on other indications. If so, we'll stop
            // this process and let whatever is "monitoring" it restart the process.
            $this->stopIfNecessary($options, $state);
        }
    }

    /**
     * Run the closure and handle exceptions.
     *
     * @param Closure $closure
     * @param SupervisorOptions $options
     * @param SupervisorState $state
     */
    protected function run(Closure $closure, SupervisorOptions $options, SupervisorState $state)
    {
        try {
            $closure();
            $this->events->dispatch(new RunSucceed($options, $state));
        } catch (\Exception $e) {
            $this->exceptions->report($e);
            $this->events->dispatch(new RunFailed($options, $state, $e));
        } catch (\Throwable $e) {
            $this->exceptions->report(new FatalThrowableError($e));
            $this->events->dispatch(new RunFailed($options, $state, $e));
        }
    }

    /**
     * Resolve the supervised command into a closure.
     *
     * @param Closure|string|object $command
     *
     * @return Closure
     */
    protected function resolveCommand($command)
    {
        if ($command instanceof Closure) {
            return $command;
        }

        if (is_string($command)) {
            $command = $this->laravel->make($command);
        }

        return function () use ($command) {
            $this->bus->dispatch($command);
        };
    }

    /**
     * Enable async signals for the process.
     *
     * @param SupervisorState $state
     */
    protected function listenForSignals(SupervisorState $state)
    {
        if ($this->supportsAsyncSignals()) {
            pcntl_async_signals(true);

            pcntl_signal(SIGTERM, function () use ($state) {
                $state->shouldQuit = true;
            });

            pcntl_signal(SIGUSR2, function () use ($state) {
                $state->paused = true;
            });

            pcntl_signal(SIGCONT, function () use ($state) {
                $state->paused = false;
            });
        }
    }

    /**
     * Determine if "async" signals are supported.
     *
     * @return bool
     */
    protected function supportsAsyncSignals()
    {
        return version_compare(PHP_VERSION, '7.1.0') >= 0 &&
            extension_loaded('pcntl');
    }

    /**
     * Get the last queue restart timestamp, or null.
     *
     * @return int|null
     */
    protected function getTimestampOfLastRestart()
    {
        if ($this->cache) {
            return $this->cache->get('illuminate:queue:restart');
        }
    }

    /**
     * Register the worker timeout handler (PHP 7.1+).
     *
     * @param  SupervisorOptions  $options
     * @return void
     */
    protected function registerTimeoutHandler(SupervisorOptions $options)
    {
        if ($options->timeout > 0 && $this->supportsAsyncSignals()) {
            // We will register a signal handler for the alarm signal so that we can kill this
            // process if it is running too long because it has frozen. This uses the async
            // signals supported in recent versions of PHP to accomplish it conveniently.
            pcntl_signal(SIGALRM, function () {
                $this->kill(1);
            });

            pcntl_alarm($options->timeout);
        }
    }

    /**
     * Determine if the daemon should process on this iteration.
     *
     * @param SupervisorOptions $options
     * @param SupervisorState $state
     *
     * @return bool
     */
    protected function shouldRun(SupervisorOptions $options, SupervisorState $state)
    {
        return ! ((! $options->force && $this->laravel->isDownForMaintenance()) ||
            $state->paused ||
            $this->events->until(new LoopBeginning($options, $state)) === false);
    }

    /**
     * Determine if the queue worker should restart.
     *
     * @param  int|null  $lastRestart
     * @return bool
     */
    protected function shouldRestart($lastRestart)
    {
        return $this->getTimestampOfLastRestart() != $lastRestart;
    }

    /**
     * Determine if the memory limit has been exceeded.
     *
     * @param  int   $memoryLimit
     *
     * @return bool
     */
    protected function memoryExceeded($memoryLimit)
    {
        return (memory_get_usage() / 1024 / 1024) >= $memoryLimit;
    }

    /**
     * Stop the process if necessary.
     *
     * @param SupervisorOptions $options
     * @param SupervisorState $state
     */
    protected function stopIfNecessary(SupervisorOptions $options, SupervisorState $state)
    {
        if ($this->memoryExceeded($options->memory)) {
            $this->stop(12);
        } elseif ($state->shouldQuit || $this->shouldRestart($state->lastRestart) ||
            $this->events->until(new LoopCompleting($options, $state)) === false) {
            $this->stop();
        }
    }

    /**
     * Pause the worker for the current loop.
     */
    protected function pause()
    {
        sleep(1);
    }

    /**
     * Stop listening and bail out of the script.
     *
     * @param  int  $status
     * @return void
     */
    protected function stop($status = 0)
    {
        $this->events->dispatch(new SupervisorStopping($status));

        exit($status);
    }

    /**
     * Kill the process.
     *
     * @param  int  $status
     * @return void
     */
    protected function kill($status = 0)
    {
        if (extension_loaded('posix')) {
            posix_kill(getmypid(), SIGKILL);
        }

        exit($status);
    }
}
