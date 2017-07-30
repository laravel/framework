<?php

namespace Illuminate\Console\Scheduling;

use Illuminate\Console\Application;
use Illuminate\Container\Container;
use Symfony\Component\Process\ProcessUtils;

class Schedule
{
    /**
     * All of the events on the schedule.
     *
     * @var \Illuminate\Console\Scheduling\Event[]
     */
    protected $events = [];

    /**
     * The mutex implementation.
     *
     * @var \Illuminate\Console\Scheduling\Mutex
     */
    protected $mutex;

    /**
     * Create a new schedule instance.
     *
     * @return void
     */
    public function __construct()
    {
        $container = Container::getInstance();

        $this->mutex = $container->bound(Mutex::class)
                                ? $container->make(Mutex::class)
                                : $container->make(CacheMutex::class);
    }

    /**
     * Add a new callback event to the schedule.
     *
     * @param  string|callable  $callback
     * @param  array   $parameters
     * @return \Illuminate\Console\Scheduling\CallbackEvent
     */
    public function call($callback, array $parameters = [])
    {
        $this->events[] = $event = new CallbackEvent(
            $this->mutex, $callback, $parameters
        );

        return $event;
    }

    /**
     * Add a new Artisan command event to the schedule.
     *
     * @param  string  $command
     * @param  array  $parameters
     * @return \Illuminate\Console\Scheduling\Event
     */
    public function command($command, array $parameters = [])
    {
        if (class_exists($command)) {
            $command = Container::getInstance()->make($command)->getName();
        }

        return $this->exec(
            Application::formatCommandString($command), $parameters
        );
    }

    /**
     * Add a new job callback event to the schedule.
     *
     * @param  object|string  $job
     * @return \Illuminate\Console\Scheduling\CallbackEvent
     */
    public function job($job)
    {
        return $this->call(function () use ($job) {
            dispatch(is_string($job) ? resolve($job) : $job);
        })->name(is_string($job) ? $job : get_class($job));
    }

    /**
     * Add a new command event to the schedule.
     *
     * @param  string  $command
     * @param  array  $parameters
     * @return \Illuminate\Console\Scheduling\Event
     */
    public function exec($command, array $parameters = [])
    {
        if (count($parameters)) {
            $command .= ' '.$this->compileParameters($parameters);
        }

        $this->events[] = $event = new Event($this->mutex, $command);

        return $event;
    }

    /**
     * Compile parameters for a command.
     *
     * @param  array  $parameters
     * @return string
     */
    protected function compileParameters(array $parameters)
    {
        return collect($parameters)->map(function ($value, $key) {
            if (is_array($value)) {
                $value = collect($value)->map(function ($value) {
                    return ProcessUtils::escapeArgument($value);
                })->implode(' ');
            } elseif (! is_numeric($value) && ! preg_match('/^(-.$|--.*)/i', $value)) {
                $value = ProcessUtils::escapeArgument($value);
            }

            return is_numeric($key) ? $value : "{$key}={$value}";
        })->implode(' ');
    }

    /**
     * Get all of the events on the schedule that are due.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return array
     */
    public function dueEvents($app)
    {
        return collect($this->events)->filter->isDue($app);
    }

    /**
     * Get all of the events on the schedule.
     *
     * @return \Illuminate\Console\Scheduling\Event[]
     */
    public function events()
    {
        return $this->events;
    }
}
