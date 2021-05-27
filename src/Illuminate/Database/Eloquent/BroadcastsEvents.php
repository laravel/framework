<?php

namespace Illuminate\Database\Eloquent;

use Illuminate\Support\Arr;

trait BroadcastsEvents
{
    /**
     * Boot the event broadcasting trait.
     *
     * @return void
     */
    public static function bootBroadcastsEvents()
    {
        static::created(function ($model) {
            $model->broadcastCreated();
        });

        static::updated(function ($model) {
            $model->broadcastUpdated();
        });

        if (method_exists(static::class, 'bootSoftDeletes')) {
            static::trashed(function ($model) {
                $model->broadcastTrashed();
            });

            static::restored(function ($model) {
                $model->broadcastRestored();
            });
        }

        static::deleted(function ($model) {
            $model->broadcastDeleted();
        });
    }

    /**
     * Broadcast that the model was created.
     *
     * @param  \Illuminate\Broadcasting\Channel|\Illuminate\Contracts\Broadcasting\HasBroadcastChannel|array|null  $channels
     * @return \Illuminate\Broadcasting\PendingBroadcast
     */
    public function broadcastCreated($channels = null)
    {
        return $this->broadcastIfBroadcastChannelsExistForEvent(
            $this->newBroadcastableModelEvent('created'), 'created', $channels
        );
    }

    /**
     * Broadcast that the model was updated.
     *
     * @param  \Illuminate\Broadcasting\Channel|\Illuminate\Contracts\Broadcasting\HasBroadcastChannel|array|null  $channels
     * @return \Illuminate\Broadcasting\PendingBroadcast
     */
    public function broadcastUpdated($channels = null)
    {
        return $this->broadcastIfBroadcastChannelsExistForEvent(
            $this->newBroadcastableModelEvent('updated'), 'updated', $channels
        );
    }

    /**
     * Broadcast that the model was trashed.
     *
     * @param  \Illuminate\Broadcasting\Channel|\Illuminate\Contracts\Broadcasting\HasBroadcastChannel|array|null  $channels
     * @return \Illuminate\Broadcasting\PendingBroadcast
     */
    public function broadcastTrashed($channels = null)
    {
        return $this->broadcastIfBroadcastChannelsExistForEvent(
            $this->newBroadcastableModelEvent('trashed'), 'trashed', $channels
        );
    }

    /**
     * Broadcast that the model was restored.
     *
     * @param  \Illuminate\Broadcasting\Channel|\Illuminate\Contracts\Broadcasting\HasBroadcastChannel|array|null  $channels
     * @return \Illuminate\Broadcasting\PendingBroadcast
     */
    public function broadcastRestored($channels = null)
    {
        return $this->broadcastIfBroadcastChannelsExistForEvent(
            $this->newBroadcastableModelEvent('restored'), 'restored', $channels
        );
    }

    /**
     * Broadcast that the model was deleted.
     *
     * @param  \Illuminate\Broadcasting\Channel|\Illuminate\Contracts\Broadcasting\HasBroadcastChannel|array|null  $channels
     * @return \Illuminate\Broadcasting\PendingBroadcast
     */
    public function broadcastDeleted($channels = null)
    {
        return $this->broadcastIfBroadcastChannelsExistForEvent(
            $this->newBroadcastableModelEvent('deleted'), 'deleted', $channels
        );
    }

    /**
     * Broadcast the given event instance if channels are configured for the model event.
     *
     * @param  mixed  $instance
     * @param  string  $event
     * @param  mixed  $channels
     * @return \Illuminate\Broadcasting\PendingBroadcast|null
     */
    protected function broadcastIfBroadcastChannelsExistForEvent($instance, $event, $channels = null)
    {
        if (! empty($this->broadcastOn($event)) || ! empty($channels)) {
            return broadcast($instance->onChannels(Arr::wrap($channels)));
        }
    }

    /**
     * Get the channels that model events should broadcast on.
     *
     * @param  string  $event
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn($event)
    {
        return [$this];
    }

    /**
     * Create a new broadcastable model event event.
     *
     * @param  string  $event
     * @return mixed
     */
    public function newBroadcastableModelEvent($event)
    {
        return new BroadcastableModelEventOccurred($this, $event);
    }
}
