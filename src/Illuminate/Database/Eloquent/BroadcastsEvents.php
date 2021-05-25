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
            broadcast($model->newBroadcastableModelEvent('created'));
        });

        static::updated(function ($model) {
            broadcast($model->newBroadcastableModelEvent('updated'));
        });

        if (method_exists(static::class, 'bootSoftDeletes')) {
            static::trashed(function ($model) {
                broadcast($model->newBroadcastableModelEvent('trashed'));
            });

            static::restored(function ($model) {
                broadcast($model->newBroadcastableModelEvent('restored'));
            });
        }

        static::deleted(function ($model) {
            broadcast($model->newBroadcastableModelEvent('deleted'));
        });
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
