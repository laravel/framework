<?php

namespace Illuminate\Database\Eloquent;

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
     * @return \Illuminate\Broadcasting\PendingBroadcast
     */
    public function broadcastCreated()
    {
        return $this->broadcastIfBroadcastChannelsExistForEvent(
            $this->newBroadcastableModelEvent('created'), 'created'
        );
    }

    /**
     * Broadcast that the model was updated.
     *
     * @return \Illuminate\Broadcasting\PendingBroadcast
     */
    public function broadcastUpdated()
    {
        return $this->broadcastIfBroadcastChannelsExistForEvent(
            $this->newBroadcastableModelEvent('updated'), 'updated'
        );
    }

    /**
     * Broadcast that the model was trashed.
     *
     * @return \Illuminate\Broadcasting\PendingBroadcast
     */
    public function broadcastTrashed()
    {
        return $this->broadcastIfBroadcastChannelsExistForEvent(
            $this->newBroadcastableModelEvent('trashed'), 'trashed'
        );
    }

    /**
     * Broadcast that the model was restored.
     *
     * @return \Illuminate\Broadcasting\PendingBroadcast
     */
    public function broadcastRestored()
    {
        return $this->broadcastIfBroadcastChannelsExistForEvent(
            $this->newBroadcastableModelEvent('restored'), 'restored'
        );
    }

    /**
     * Broadcast that the model was deleted.
     *
     * @return \Illuminate\Broadcasting\PendingBroadcast
     */
    public function broadcastDeleted()
    {
        return $this->broadcastIfBroadcastChannelsExistForEvent(
            $this->newBroadcastableModelEvent('deleted'), 'deleted'
        );
    }

    /**
     * Broadcast the given event instance if channels are configured for the model event.
     *
     * @param  mixed  $instance
     * @param  string  $event
     * @return \Illuminate\Broadcasting\PendingBroadcast|null
     */
    protected function broadcastIfBroadcastChannelsExistForEvent($instance, $event)
    {
        if (! empty($this->broadcastOn($event))) {
            return broadcast($instance);
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
