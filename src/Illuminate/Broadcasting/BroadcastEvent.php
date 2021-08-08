<?php

namespace Illuminate\Broadcasting;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\Factory as BroadcastingFactory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use ReflectionClass;
use ReflectionProperty;

class BroadcastEvent implements ShouldQueue
{
    use Queueable;

    /**
     * The event instance.
     *
     * @var mixed
     */
    public $event;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout;

    /**
     * Create a new job handler instance.
     *
     * @param  mixed  $event
     * @return void
     */
    public function __construct($event)
    {
        $this->event = $event;
        $this->tries = property_exists($event, 'tries') ? $event->tries : null;
        $this->timeout = property_exists($event, 'timeout') ? $event->timeout : null;
        $this->afterCommit = property_exists($event, 'afterCommit') ? $event->afterCommit : null;
    }

    /**
     * Handle the queued job.
     *
     * @param  \Illuminate\Contracts\Broadcasting\Factory  $manager
     * @return void
     */
    public function handle(BroadcastingFactory $manager)
    {
        $name = method_exists($this->event, 'broadcastAs')
                ? $this->event->broadcastAs() : get_class($this->event);

        $channels = Arr::wrap($this->event->broadcastOn());

        if (empty($channels)) {
            return;
        }

        $connections = method_exists($this->event, 'broadcastConnections')
                            ? $this->event->broadcastConnections()
                            : [null];

        $payload = $this->getPayloadFromEvent($this->event);

        foreach ($connections as $connection) {
            $manager->connection($connection)->broadcast(
                $channels, $name, $payload
            );
        }
    }

    /**
     * Get the payload for the given event.
     *
     * @param  mixed  $event
     * @return array
     */
    protected function getPayloadFromEvent($event)
    {
        if (method_exists($event, 'broadcastWith') &&
            ! is_null($payload = $event->broadcastWith())) {
            return array_merge($payload, ['socket' => data_get($event, 'socket')]);
        }

        $payload = [];

        foreach ((new ReflectionClass($event))->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $payload[$property->getName()] = $this->formatProperty($property->getValue($event));
        }

        unset($payload['broadcastQueue']);

        return $payload;
    }

    /**
     * Format the given value for a property.
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function formatProperty($value)
    {
        if ($value instanceof Arrayable) {
            return $value->toArray();
        }

        return $value;
    }

    /**
     * Get the display name for the queued job.
     *
     * @return string
     */
    public function displayName()
    {
        return get_class($this->event);
    }

    /**
     * Prepare the instance for cloning.
     *
     * @return void
     */
    public function __clone()
    {
        $this->event = clone $this->event;
    }
}
