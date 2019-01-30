<?php

namespace Illuminate\Foundation\Testing\Concerns;

use Mockery;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcherContract;
use Illuminate\Contracts\Events\Dispatcher as EventsDispatcherContract;
use Illuminate\Contracts\Notifications\Dispatcher as NotificationDispatcher;

trait MocksApplicationServices
{
    /**
     * All of the fired events.
     *
     * @var array
     */
    protected $firedEvents = [];

    /**
     * All of the fired model events.
     *
     * @var array
     */
    protected $firedModelEvents = [];

    /**
     * All of the dispatched jobs.
     *
     * @var array
     */
    protected $dispatchedJobs = [];

    /**
     * All of the dispatched notifications.
     *
     * @var array
     */
    protected $dispatchedNotifications = [];

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

            $this->assertEmpty(
                $eventsNotFired = array_diff($events, $fired),
                'These expected events were not fired: ['.implode(', ', $eventsNotFired).']'
            );
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
            $this->assertEmpty(
                $fired = $this->getFiredEvents($events),
                'These unexpected events were fired: ['.implode(', ', $fired).']'
            );
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
        $mock = Mockery::mock(EventsDispatcherContract::class)->shouldIgnoreMissing();

        $mock->shouldReceive('dispatch')->andReturnUsing(function ($called) {
            $this->firedEvents[] = $called;
        });

        $this->app->instance('events', $mock);

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

            $this->assertEmpty(
                $jobsNotDispatched = array_diff($jobs, $dispatched),
                'These expected jobs were not dispatched: ['.implode(', ', $jobsNotDispatched).']'
            );
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
            $this->assertEmpty(
                $dispatched = $this->getDispatchedJobs($jobs),
                'These unexpected jobs were dispatched: ['.implode(', ', $dispatched).']'
            );
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
        $mock = Mockery::mock(BusDispatcherContract::class)->shouldIgnoreMissing();

        $mock->shouldReceive('dispatch', 'dispatchNow')->andReturnUsing(function ($dispatched) {
            $this->dispatchedJobs[] = $dispatched;
        });

        $this->app->instance(
            BusDispatcherContract::class, $mock
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

    /**
     * Mock the notification dispatcher so all notifications are silenced.
     *
     * @return $this
     */
    protected function withoutNotifications()
    {
        $mock = Mockery::mock(NotificationDispatcher::class);

        $mock->shouldReceive('send')->andReturnUsing(function ($notifiable, $instance, $channels = []) {
            $this->dispatchedNotifications[] = compact(
                'notifiable', 'instance', 'channels'
            );
        });

        $this->app->instance(NotificationDispatcher::class, $mock);

        return $this;
    }

    /**
     * Specify a notification that is expected to be dispatched.
     *
     * @param  mixed  $notifiable
     * @param  string  $notification
     * @return $this
     */
    protected function expectsNotification($notifiable, $notification)
    {
        $this->withoutNotifications();

        $this->beforeApplicationDestroyed(function () use ($notifiable, $notification) {
            foreach ($this->dispatchedNotifications as $dispatched) {
                $notified = $dispatched['notifiable'];

                if (($notified === $notifiable ||
                     $notified->getKey() == $notifiable->getKey()) &&
                    get_class($dispatched['instance']) === $notification
                ) {
                    return $this;
                }
            }

            $this->fail('The following expected notification were not dispatched: ['.$notification.']');
        });

        return $this;
    }
}
