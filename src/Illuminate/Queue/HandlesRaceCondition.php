<?php

namespace Illuminate\Queue;

use LogicException;

trait HandlesRaceCondition
{
    /**
     * Slot being used by the current command instance.
     *
     * @var string
     */
    protected $slot;

    /**
     * Returns the Slot being used by the command.
     *
     * @return string
     */
    public function getSlot()
    {
        return $this->slot;
    }

    /**
     * Sets the Slot to use by the command.
     *
     * @param $slot
     */
    public function setSlot($slot)
    {
        $this->slot = $slot;
    }

    /**
     * The initial slot the command should start from.
     *
     * @return int
     */
    public function startFrom()
    {
        return 0;
    }

    /**
     * Returns the next slot from a given slot.
     *
     * @param $next
     * @return mixed
     */
    public function next($next)
    {
        return ++$next;
    }

    /**
     * Returns the Job instance.
     *
     * @return \Illuminate\Contracts\Queue\Job
     * @throws \LogicException
     */
    public function getJob()
    {
        if (! in_array(InteractsWithQueue::class, class_uses_recursive($this))) {
            throw new LogicException('Class '.class_basename($this).' needs InteractsWithQueue trait to work.');
        }

        return $this->job;
    }
}
