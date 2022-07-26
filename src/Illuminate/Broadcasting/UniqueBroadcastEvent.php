<?php

namespace Illuminate\Broadcasting;

use Illuminate\Contracts\Queue\ShouldBeUnique;

class UniqueBroadcastEvent extends BroadcastEvent implements ShouldBeUnique
{
    /**
     * The unique lock identifier.
     *
     * @var mixed
     */
    public $uniqueId;

    /**
     * The number of seconds the unique lock should be maintained.
     *
     * @var int
     */
    public $uniqueFor;

    /**
     * The cache repository implementation that should be used to obtain unique locks.
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
        $this->uniqueId = get_class($event);

        if (method_exists($event, 'uniqueId')) {
            $this->uniqueId = $event->uniqueId();
        } elseif (property_exists($event, 'uniqueId')) {
            $this->uniqueId = $event->uniqueId;
        }

        if (method_exists($event, 'uniqueFor')) {
            $this->uniqueFor = $event->uniqueFor();
        } elseif (property_exists($event, 'uniqueFor')) {
            $this->uniqueFor = $event->uniqueFor;
        }

        if (method_exists($event, 'uniqueVia')) {
            $this->uniqueVia = $event->uniqueVia();
        } elseif (property_exists($event, 'uniqueVia')) {
            $this->uniqueVia = $event->uniqueVia;
        }

        parent::__construct($event);
    }
}
