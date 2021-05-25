<?php

namespace Illuminate\Database\Eloquent;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class BroadcastableModelEventOccurred implements ShouldBroadcast
{
    use InteractsWithSockets;

    /**
     * The model instance corresponding to the event.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    public $model;

    /**
     * The event name (created, updated, etc.).
     *
     * @var string
     */
    protected $event;

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
        $this->event = $event;
    }

    /**
     * The channels the event should broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return collect($this->model->broadcastOn($this->event) ?: [])->map(function ($channel) {
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
        return class_basename($this->model).ucfirst($this->event);
    }
}
