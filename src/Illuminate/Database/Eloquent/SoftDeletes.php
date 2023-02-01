<?php

namespace Illuminate\Database\Eloquent;

/**
 * @method static \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder withoutTrashed()
 */
trait SoftDeletes
{
    /**
     * Indicates if the model is currently force deleting.
     *
     * @var bool
     */
    protected $forceDeleting = false;

    /**
     * Boot the soft deleting trait for a model.
     *
     * @return void
     */
    public static function bootSoftDeletes()
    {
        static::addGlobalScope(new SoftDeletingScope);
    }

    /**
     * Initialize the soft deleting trait for an instance.
     *
     * @return void
     */
    public function initializeSoftDeletes()
    {
        if (! isset($this->casts[$this->getDeletedAtColumn()])) {
            $this->casts[$this->getDeletedAtColumn()] = 'datetime';
        }
    }

    /**
     * Force a hard delete on a soft deleted model.
     *
     * @return bool|null
     */
    public function forceDelete()
    {
        if ($this->fireModelEvent('forceDeleting') === false) {
            return false;
        }

        $this->forceDeleting = true;

        return tap($this->delete(), function ($deleted) {
            $this->forceDeleting = false;

            if ($deleted) {
                $this->fireModelEvent('forceDeleted', false);
            }
        });
    }

    /**
     * Force a hard delete on a soft deleted model without raising any events.
     *
     * @return bool|null
     */
    public function forceDeleteQuietly()
    {
        return static::withoutEvents(fn () => $this->forceDelete());
    }

    /**
     * Perform the actual delete query on this model instance.
     *
     * @return mixed
     */
    protected function performDeleteOnModel()
    {
        if ($this->forceDeleting) {
            return tap($this->setKeysForSaveQuery($this->newModelQuery())->forceDelete(), function () {
                $this->exists = false;
            });
        }

        return $this->runSoftDelete();
    }

    /**
     * Perform the actual delete query on this model instance.
     *
     * @return void
     */
    protected function runSoftDelete()
    {
        $query = $this->setKeysForSaveQuery($this->newModelQuery());

        $time = $this->freshTimestamp();

        $columns = [$this->getDeletedAtColumn() => $this->fromDateTime($time)];

        $this->{$this->getDeletedAtColumn()} = $time;

        if ($this->usesTimestamps() && ! is_null($this->getUpdatedAtColumn())) {
            $this->{$this->getUpdatedAtColumn()} = $time;

            $columns[$this->getUpdatedAtColumn()] = $this->fromDateTime($time);
        }

        $query->update($columns);

        $this->syncOriginalAttributes(array_keys($columns));

        $this->fireModelEvent('trashed', false);
    }

    /**
     * Restore a soft-deleted model instance.
     *
     * @return bool
     */
    public function restore()
    {
        // If the restoring event does not return false, we will proceed with this
        // restore operation. Otherwise, we bail out so the developer will stop
        // the restore totally. We will clear the deleted timestamp and save.
        if ($this->fireModelEvent('restoring') === false) {
            return false;
        }

        $this->{$this->getDeletedAtColumn()} = null;

        // Once we have saved the model, we will fire the "restored" event so this
        // developer will do anything they need to after a restore operation is
        // totally finished. Then we will return the result of the save call.
        $this->exists = true;

        $result = $this->save();

        $this->fireModelEvent('restored', false);

        return $result;
    }

    /**
     * Restore a soft-deleted model instance without raising any events.
     *
     * @return bool
     */
    public function restoreQuietly()
    {
        return static::withoutEvents(fn () => $this->restore());
    }

    /**
     * Determine if the model instance has been soft-deleted.
     *
     * @return bool
     */
    public function trashed()
    {
        return ! is_null($this->{$this->getDeletedAtColumn()});
    }

    /**
     * Register a "softDeleted" model event callback with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function softDeleted($callback)
    {
        static::registerModelEvent('trashed', $callback);
    }

    /**
     * Register a "restoring" model event callback with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function restoring($callback)
    {
        static::registerModelEvent('restoring', $callback);
    }

    /**
     * Register a "restored" model event callback with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function restored($callback)
    {
        static::registerModelEvent('restored', $callback);
    }

    /**
     * Register a "forceDeleting" model event callback with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function forceDeleting($callback)
    {
        static::registerModelEvent('forceDeleting', $callback);
    }

    /**
     * Register a "forceDeleted" model event callback with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function forceDeleted($callback)
    {
        static::registerModelEvent('forceDeleted', $callback);
    }

    /**
     * Determine if the model is currently force deleting.
     *
     * @return bool
     */
    public function isForceDeleting()
    {
        return $this->forceDeleting;
    }

    /**
     * Get the name of the "deleted at" column.
     *
     * @return string
     */
    public function getDeletedAtColumn()
    {
        return defined(static::class.'::DELETED_AT') ? static::DELETED_AT : 'deleted_at';
    }

    /**
     * Get the fully qualified "deleted at" column.
     *
     * @return string
     */
    public function getQualifiedDeletedAtColumn()
    {
        return $this->qualifyColumn($this->getDeletedAtColumn());
    }
}
