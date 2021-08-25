<?php

namespace Illuminate\Database\Eloquent;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class BroadcastableModelEventOccurred implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    /**
     * The model instance corresponding to the event.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    public $model;

    /**
     * The model's class name.
     *
     * @var string
     */
    protected $modelClass;

    /**
     * The event name (created, updated, etc.).
     *
     * @var string
     */
    protected $event;

    /**
     * The custom name that should be used when broadcasting the event.
     *
     * @var string|null
     */
    protected $broadcastableAs;

    /**
     * The channels that the event should be broadcast on.
     *
     * @var array
     */
    protected $channels = [];

    /**
     * The default channels that the event should be broadcast on if no other channels are provided.
     *
     * @var array
     */
    protected $defaultChannels = [];

    /**
     * The queue connection that should be used to queue the broadcast job.
     *
     * @var string
     */
    public $connection;

    /**
     * The queue that should be used to queue the broadcast job.
     *
     * @var string
     */
    public $queue;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $event
     * @return void
     */
    public function __construct($model, $event)
    {
        $this->model = $model;
        $this->modelClass = get_class($model);
        $this->event = $event;

        if ($this->event === 'deleted' &&
            ! method_exists($model, 'bootSoftDeletes')) {
            $this->model = $this->model->getKey();
        }
    }

    /**
     * The channels the event should broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        $channels = empty($this->channels)
                ? $this->defaultChannels
                : $this->channels;

        return collect($channels)->map(function ($channel) {
            return $channel instanceof Model ? new PrivateChannel($channel) : $channel;
        })->all();
    }

    /**
     * The name the event should broadcast as.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return $this->broadcastableAs ?: class_basename($this->modelClass).ucfirst($this->event);
    }

    /**
     * Specify the name the model event is broadcastable as.
     *
     * @param  string|null  $name
     * @return $this
     */
    public function broadcastableAs(?string $name)
    {
        $this->broadcastableAs = $name;

        return $this;
    }

    /**
     * Get the data that should be sent with the broadcasted event.
     *
     * @return array|null
     */
    public function broadcastWith()
    {
        return is_object($this->model) && method_exists($this->model, 'broadcastWith')
            ? $this->model->broadcastWith($this->event)
            : null;
    }

    /**
     * Manually specify the channels the event should broadcast on.
     *
     * @param  array  $channels
     * @return $this
     */
    public function onChannels(array $channels)
    {
        $this->channels = $channels;

        return $this;
    }

    /**
     * Specify the default channels the event should broadcast on if no others are specified.
     *
     * @param  array  $channels
     * @return $this
     */
    public function onDefaultChannels(array $channels)
    {
        $this->defaultChannels = $channels;

        return $this;
    }

    /**
     * Get the event name.
     *
     * @return string
     */
    public function event()
    {
        return $this->event;
    }
}
