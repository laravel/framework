<?php

namespace Illuminate\Foundation\Testing\Concerns;

use Mockery;
use Exception;

trait MocksApplicationServices
{
    /**
     * All of the fired events.
     *
     * @var array
     */
    protected $firedEvents = [];

    /**
     * All of the dispatched jobs.
     *
     * @var array
     */
    protected $dispatchedJobs = [];

    /**
     * Specify a list of events that should be fired for the given operation.
     *
     * These events will be mocked, so that handlers will not actually be executed.
     *
     * @param  array|string  $events
     * @return $this
     *
     * @throws \Exception
     */
    public function expectsEvents($events)
    {
        $events = is_array($events) ? $events : func_get_args();

        $this->withoutEvents();

        $this->beforeApplicationDestroyed(function () use ($events) {
            $fired = $this->getFiredEvents($events);

            if ($eventsNotFired = array_diff($events, $fired)) {
                throw new Exception(
                    'These expected events were not fired: ['.implode(', ', $eventsNotFired).']'
                );
            }
        });

        return $this;
    }

    /**
     * Specify a list of events that should not be fired for the given operation.
     *
     * These events will be mocked, so that handlers will not actually be executed.
     *
     * @param  array|string  $events
     * @return $this
     */
    public function doesntExpectEvents($events)
    {
        $events = is_array($events) ? $events : func_get_args();

        $this->withoutEvents();

        $this->beforeApplicationDestroyed(function () use ($events) {
            if ($fired = $this->getFiredEvents($events)) {
                throw new Exception(
                    'These unexpected events were fired: ['.implode(', ', $fired).']'
                );
            }
        });

        return $this;
    }

    /**
     * Mock the event dispatcher so all events are silenced and collected.
     *
     * @return $this
     */
    protected function withoutEvents()
    {
        $mock = Mockery::mock('Illuminate\Contracts\Events\Dispatcher');

        $mock->shouldReceive('fire')->andReturnUsing(function ($called) {
            $this->firedEvents[] = $called;
        });

        $this->app->instance('events', $mock);

        return $this;
    }

    /**
     * Specify a list of observers that will not run for the given operation.
     *
     * @param  array|string  $observers
     * @return $this
     */
    public function withoutObservers($observers)
    {
        $observers = is_array($observers) ? $observers : [$observers];

        array_map(function ($observer) {
            $this->app->bind($observer, function () use ($observer) {
                return $this->getMockBuilder($observer)->disableOriginalConstructor()->getMock();
            });
        }, $observers);

        return $this;
    }

    /**
     * Filter the given events against the fired events.
     *
     * @param  array  $events
     * @return array
     */
    protected function getFiredEvents(array $events)
    {
        return $this->getDispatched($events, $this->firedEvents);
    }

    /**
     * Specify a list of jobs that should be dispatched for the given operation.
     *
     * These jobs will be mocked, so that handlers will not actually be executed.
     *
     * @param  array|string  $jobs
     * @return $this
     */
    protected function expectsJobs($jobs)
    {
        $jobs = is_array($jobs) ? $jobs : func_get_args();

        $this->withoutJobs();

        $this->beforeApplicationDestroyed(function () use ($jobs) {
            $dispatched = $this->getDispatchedJobs($jobs);

            if ($jobsNotDispatched = array_diff($jobs, $dispatched)) {
                throw new Exception(
                    'These expected jobs were not dispatched: ['.implode(', ', $jobsNotDispatched).']'
                );
            }
        });

        return $this;
    }

    /**
     * Specify a list of jobs that should not be dispatched for the given operation.
     *
     * These jobs will be mocked, so that handlers will not actually be executed.
     *
     * @param  array|string  $jobs
     * @return $this
     */
    protected function doesntExpectJobs($jobs)
    {
        $jobs = is_array($jobs) ? $jobs : func_get_args();

        $this->withoutJobs();

        $this->beforeApplicationDestroyed(function () use ($jobs) {
            if ($dispatched = $this->getDispatchedJobs($jobs)) {
                throw new Exception(
                    'These unexpected jobs were dispatched: ['.implode(', ', $dispatched).']'
                );
            }
        });

        return $this;
    }

    /**
     * Mock the job dispatcher so all jobs are silenced and collected.
     *
     * @return $this
     */
    protected function withoutJobs()
    {
        $mock = Mockery::mock('Illuminate\Contracts\Bus\Dispatcher');

        $mock->shouldReceive('dispatch')->andReturnUsing(function ($dispatched) {
            $this->dispatchedJobs[] = $dispatched;
        });

        $this->app->instance(
            'Illuminate\Contracts\Bus\Dispatcher', $mock
        );

        return $this;
    }

    /**
     * Filter the given jobs against the dispatched jobs.
     *
     * @param  array  $jobs
     * @return array
     */
    protected function getDispatchedJobs(array $jobs)
    {
        return $this->getDispatched($jobs, $this->dispatchedJobs);
    }

    /**
     * Filter the given classes against an array of dispatched classes.
     *
     * @param  array  $classes
     * @param  array  $dispatched
     * @return array
     */
    protected function getDispatched(array $classes, array $dispatched)
    {
        return array_filter($classes, function ($class) use ($dispatched) {
            return $this->wasDispatched($class, $dispatched);
        });
    }

    /**
     * Check if the given class exists in an array of dispatched classes.
     *
     * @param  string  $needle
     * @param  array  $haystack
     * @return bool
     */
    protected function wasDispatched($needle, array $haystack)
    {
        foreach ($haystack as $dispatched) {
            if ((is_string($dispatched) && ($dispatched === $needle || is_subclass_of($dispatched, $needle))) ||
                $dispatched instanceof $needle) {
                return true;
            }
        }

        return false;
    }
}
