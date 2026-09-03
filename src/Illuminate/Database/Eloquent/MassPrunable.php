<?php

namespace Illuminate\Database\Eloquent;

use Illuminate\Database\Events\ModelsPruned;
use LogicException;

trait MassPrunable
{
    /**
     * Prune all prunable models in the database.
     */
    public function pruneAll(int $chunkSize = 1000): int
    {
        $softDeletable = static::isSoftDeletable();

        $query = tap($this->prunable(), function ($query) use ($chunkSize, $softDeletable) {
            $query->when($softDeletable, fn ($query) => $query->withTrashed())
                ->when(! $query->getQuery()->limit, fn ($query) => $query->limit($chunkSize));
        });

        $total = 0;

        do {
            $total += $count = $softDeletable
                ? $query->forceDelete()
                : $query->delete();

            if ($count > 0) {
                event(new ModelsPruned(static::class, $total));
            }
        } while ($count > 0);

        return $total;
    }

    /**
     * Get the prunable model query.
     *
     * @return \Illuminate\Database\Eloquent\Builder<static>
     *
     * @throws \LogicException
     */
    public function prunable()
    {
        throw new LogicException('Please implement the prunable method on your model.');
    }
}
