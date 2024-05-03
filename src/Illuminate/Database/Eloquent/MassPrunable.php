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
        $total = 0;
        $isSoftDeletes = in_array(SoftDeletes::class, class_uses_recursive(get_class($this)));

        $query = $this->prunable()->limit($chunkSize);

        do {
            $count = $isSoftDeletes ? $query->forceDelete() : $query->delete();
            $total += $count;

            if ($count > 0 && $total % $chunkSize === 0) {
                event(new ModelsPruned(static::class, $total));
            }
        } while ($count > 0);

        if ($total > 0 && $total % $chunkSize !== 0) {
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
