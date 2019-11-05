<?php

namespace Illuminate\Database\Eloquent;

interface SoftDeletable
{
    /**
     * Force a hard delete on a soft deleted model.
     *
     * @return bool|null
     */
    public function forceDelete();

    /**
     * Restore a soft-deleted model instance.
     *
     * @return bool|null
     */
    public function restore();

    /**
     * Determine if the model instance has been soft-deleted.
     *
     * @return bool
     */
    public function trashed();

    /**
     * Register a restoring model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function restoring($callback);

    /**
     * Register a restored model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function restored($callback);

    /**
     * Determine if the model is currently force deleting.
     *
     * @return bool
     */
    public function isForceDeleting();
}
