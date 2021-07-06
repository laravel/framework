<?php

namespace Illuminate\Database\Eloquent;

use Illuminate\Database\Events\ModelsPruned;
use LogicException;

trait Prunable
{
    /**
     * Get the prunable model query.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function prunable()
    {
        throw new LogicException('Please implement the prunable method on your model.');
    }

    /**
     * Prune all prunable models in the database.
     *
     * @return int
     */
    public function pruneAll()
    {
        $total = 0;

        $this->prunable()
            ->when(in_array(SoftDeletes::class, class_uses_recursive(get_class($this))), function ($query) {
                $query->withTrashed();
            })->chunkById(1000, function ($models) use (&$total) {
                $models->each->prune();
                $total += $models->count();

                event(new ModelsPruned(static::class, $total));
            });

        return $total;
    }

    /**
     * Prune the model in the database.
     *
     * @return bool|null
     */
    public function prune()
    {
        return in_array(SoftDeletes::class, class_uses_recursive(get_class($this)))
                ? $this->forceDelete()
                : $this->delete();
    }
}
