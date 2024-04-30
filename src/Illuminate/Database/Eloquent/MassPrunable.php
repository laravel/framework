<?php

namespace Illuminate\Database\Eloquent;

use Illuminate\Database\Events\ModelsPruned;
use LogicException;

trait MassPrunable
{
    /**
     * Prune all prunable models in the database.
     *
     * @param  int  $chunkSize
     * @return int
     */
    public function pruneAll(int $chunkSize = 1000)
    {
        $query = $this->prunable();
        $total = 0;
        $isSoftDeletes = in_array(SoftDeletes::class, class_uses_recursive(get_class($this)));

        $query->chunk($chunkSize, function ($models) use (&$total, $isSoftDeletes) {
            $count = $isSoftDeletes ? $models->forceDelete() : $models->delete();
            $total += $count;
        });

        if ($total > 0) {
            event(new ModelsPruned(static::class, $total));
        }

        return $total;
    }

    /**
     * Get the prunable model query.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function prunable()
    {
        throw new LogicException('Please implement the prunable method on your model.');
    }
}
