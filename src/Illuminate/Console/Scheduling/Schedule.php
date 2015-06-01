<?php

namespace Illuminate\Console\Scheduling;

use Illuminate\Contracts\Foundation\Application;

class Schedule
{
    /**
     * All of the events on the schedule.
     *
     * @var array
     */
    protected $events = [];

    /**
     * Add a new callback event to the schedule.
     *
     * @param  string  $callback
     * @param  array   $parameters
     * @return \Illuminate\Console\Scheduling\Event
     */
    public function call($callback, array $parameters = [])
    {
        $this->events[] = $event = new CallbackEvent($callback, $parameters);

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
        return $this->exec(PHP_BINARY.' artisan '.$command, $parameters);
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

        $this->events[] = $event = new Event($command);

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
            return is_numeric($key) ? $value : $key.'="'.addslashes($value).'"';
        })->implode(' ');
    }

    /**
     * Get all of the events on the schedule.
     *
     * @return array
     */
    public function events()
    {
        return $this->events;
    }

    /**
     * Get all of the events on the schedule that are due.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return array
     */
    public function dueEvents(Application $app)
    {
        return array_filter($this->events, function ($event) use ($app) {
            return $event->isDue($app);
        });
    }
}
