<?php

namespace Illuminate\Broadcasting;

use ReflectionClass;
use ReflectionProperty;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Broadcasting\Broadcaster;

class BroadcastEvent
{
    /**
     * The broadcaster implementation.
     *
     * @var \Illuminate\Contracts\Broadcasting\Broadcaster
     */
    protected $broadcaster;

    /**
     * Create a new job handler instance.
     *
     * @param  \Illuminate\Contracts\Broadcasting\Broadcaster  $broadcaster
     * @return void
     */
    public function __construct(Broadcaster $broadcaster)
    {
        $this->broadcaster = $broadcaster;
    }

    /**
     * Handle the queued job.
     *
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  array  $data
     * @return void
     */
    public function fire(Job $job, array $data)
    {
        $event = unserialize($data['event']);

        $name = method_exists($event, 'broadcastAs')
                ? $event->broadcastAs() : get_class($event);

        $channels = $event->broadcastOn();

        if (! is_array($channels)) {
            $channels = [$channels];
        }

        $this->broadcaster->broadcast(
            $channels, $name, $this->getPayloadFromEvent($event)
        );

        $job->delete();
    }

    /**
     * Get the payload for the given event.
     *
     * @param  mixed  $event
     * @return array
     */
    protected function getPayloadFromEvent($event)
    {
        $payload = [];

        foreach((new ReflectionClass($event))->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $payload[$property->getName()] = $this->formatProperty($property->getValue($event));
        }

        if (method_exists($event, 'broadcastWith')) {
            // In order to ease the developer we'll allow
            // the developer to not need to explicit pass in the
            // socket themselves.
            if (array_key_exists('socket', $payload)) {
                return $event->broadcastWith() + ['socket' => $payload['socket']];
            }

            return $event->broadcastWith();
        }

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
}
