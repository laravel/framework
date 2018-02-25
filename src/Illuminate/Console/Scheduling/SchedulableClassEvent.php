<?php

namespace Illuminate\Console\Scheduling;

use LogicException;
use InvalidArgumentException;
use Illuminate\Support\Collection;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\EventMutex;
use Illuminate\Contracts\Container\Container;

class SchedulableClassEvent extends Event{


    //TODO custom code here!

    //use the props of the trait (and manage frequencies etc)

    /**
     * The schedulable class name to use.
     *
     * @var string
     */
    protected $schedulableClass;
    
    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Console\Scheduling\EventMutex  $mutex
     * @param  string  $schedulableClass
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(EventMutex $mutex, $schedulableClass)
    {
        $schedulableClass = is_string($schedulableClass) ? resolve($schedulableClass) : $schedulableClass;

        if (!in_array('Illuminate\Console\Scheduling\Schedulable',class_uses($schedulableClass))) {
            throw new InvalidArgumentException(
                'Schedulable trait was not found on this class'
            );
        }

        $this->mutex = $mutex;
        $this->schedulableClass = $schedulableClass;
    }

    /**
     * Run the given event.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return mixed
     *
     * @throws \Exception
     */
    public function run(Container $container)
    {
        if ($this->description && $this->withoutOverlapping &&
            ! $this->mutex->create($this)) {
            return;
        }

        register_shutdown_function(function () {
            $this->removeMutex();
        });

        parent::callBeforeCallbacks($container);

        try {
            $resolved = resolve($this->schedulableClass);

            $due_items = $resolved::areDue();
            
            $due_items = collect(is_array($due_items) ||$due_items instanceof Collection  ? $due_items : array($due_items));

            $due_items->each->runSchedule();
        } finally {
            $this->removeMutex();

            parent::callAfterCallbacks($container);
        }

        return;
    }

    /**
     * Clear the mutex for the event.
     *
     * @return void
     */
    protected function removeMutex()
    {
        if ($this->description) {
            $this->mutex->forget($this);
        }
    }

    /**
     * Do not allow the event to overlap each other.
     *
     * @param  int  $expiresAt
     * @return $this
     *
     * @throws \LogicException
     */
    public function withoutOverlapping($expiresAt = 1440)
    {
        if (! isset($this->description)) {
            throw new LogicException(
                "A scheduled event name is required to prevent overlapping. Use the 'name' method before 'withoutOverlapping'."
            );
        }

        $this->withoutOverlapping = true;

        $this->expiresAt = $expiresAt;

        return $this->skip(function () {
            return $this->mutex->exists($this);
        });
    }

    /**
     * Allow the event to only run on one server for each cron expression.
     *
     * @return $this
     *
     * @throws \LogicException
     */
    public function onOneServer()
    {
        if (! isset($this->description)) {
            throw new LogicException(
                "A scheduled event name is required to only run on one server. Use the 'name' method before 'onOneServer'."
            );
        }

        $this->onOneServer = true;

        return $this;
    }

    /**
     * Get the mutex name for the scheduled command.
     *
     * @return string
     */
    public function mutexName()
    {
        return 'framework/schedule-'.sha1($this->description);
    }

    /**
     * Get the summary of the event for display.
     *
     * @return string
     */
    public function getSummaryForDisplay()
    {
        if (is_string($this->description)) {
            return $this->description;
        }

        return is_string($this->callback) ? $this->callback : 'Closure';
    }


}
