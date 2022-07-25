<?php

namespace Illuminate\Broadcasting;

use Illuminate\Contracts\Queue\ShouldBeUnique;

class UniqueBroadcastEvent extends BroadcastEvent implements ShouldBeUnique
{
    /**
     * How long the lock should last in seconds
     *
     * @var int
     */
    public $uniqueFor;

    /**
     * Identifier for lock
     *
     * @var mixed
     */
    public $uniqueId;

    /**
     * Cache repository that should be used for lock
     *
     * @var \Illuminate\Contracts\Cache\Repository
     */
    public $uniqueVia;

    /**
     * Create a new job handler instance.
     *
     * @param  mixed  $event
     * @return void
     */
    public function __construct($event)
    {
        if (method_exists($event, 'uniqueFor')) {
            $this->uniqueFor = $event->uniqueFor();
        } elseif (property_exists($event, 'uniqueFor')) {
            $this->uniqueFor = $event->uniqueFor;
        }

        $this->uniqueId = get_class($event);
        if (method_exists($event, 'uniqueId')) {
            $this->uniqueId = $event->uniqueId();
        } elseif (property_exists($event, 'uniqueId')) {
            $this->uniqueId = $event->uniqueId;
        }

        if (method_exists($event, 'uniqueVia')) {
            $this->uniqueVia = $event->uniqueVia();
        } elseif (property_exists($event, 'uniqueVia')) {
            $this->uniqueVia = $event->uniqueVia;
        }

        parent::__construct($event);
    }
}
