<?php

namespace Illuminate\Database\Eloquent;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Events\ModelsPruned;
use Illuminate\Database\Events\ModelsSoftPruned;
use LogicException;
use Throwable;

trait Prunable
{
    /**
     * Prune all prunable models in the database.
     *
     * @param  int  $chunkSize
     * @return int
     *
     * @throws \Throwable
     */
    public function pruneAll(int $chunkSize = 1000)
    {
        $hard = $this->prunable();
        $soft = $this->softPrunable();

        if (is_null($hard) && is_null($soft)) {
            throw new LogicException('Please implement the prunable or softPrunable method on your model.');
        }

        if (! is_null($soft) && ! static::isSoftDeletable()) {
            throw new LogicException(sprintf(
                'Model [%s] uses soft pruning but does not use the SoftDeletes trait.',
                static::class
            ));
        }

        $total = 0;

        // Run the hard prune window first: if a record happened to match both windows, soft
        // pruning it first would let the (withTrashed) hard prune window force delete it in
        // the same pass. The two windows are expected to be disjoint regardless.
        if (! is_null($hard)) {
            $hard->when(static::isSoftDeletable(), function ($query) {
                $query->withTrashed();
            });

            $total += $this->pruneQuery($hard, $chunkSize, 'prune', ModelsPruned::class);
        }

        if (! is_null($soft)) {
            $total += $this->pruneQuery($soft, $chunkSize, 'softPrune', ModelsSoftPruned::class);
        }

        return $total;
    }

    /**
     * Prune the records matching the given query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @param  int  $chunkSize
     * @param  string  $method
     * @param  class-string  $event
     * @return int
     *
     * @throws \Throwable
     */
    private function pruneQuery($query, int $chunkSize, string $method, string $event)
    {
        $count = 0;

        $query->chunkById($chunkSize, function ($models) use ($method, $event, &$count) {
            $models->each(function ($model) use ($method, &$count) {
                try {
                    $model->{$method}();

                    $count++;
                } catch (Throwable $e) {
                    $handler = app(ExceptionHandler::class);

                    if ($handler) {
                        $handler->report($e);
                    } else {
                        throw $e;
                    }
                }
            });

            event(new $event(static::class, $count));
        });

        return $count;
    }

    /**
     * Get the prunable model query.
     *
     * @return \Illuminate\Database\Eloquent\Builder<static>|null
     */
    public function prunable()
    {
        return null;
    }

    /**
     * Get the soft prunable model query.
     *
     * @return \Illuminate\Database\Eloquent\Builder<static>|null
     */
    public function softPrunable()
    {
        return null;
    }

    /**
     * Prune the model in the database.
     *
     * @return bool|null
     */
    public function prune()
    {
        $this->pruning();

        return static::isSoftDeletable()
            ? $this->forceDelete()
            : $this->delete();
    }

    /**
     * Soft prune the model in the database.
     *
     * The record is only soft deleted, so it remains restorable until it is later hard
     * pruned (e.g. through the model's prunable query).
     *
     * @return bool|null
     */
    public function softPrune()
    {
        $this->softPruning();

        return $this->delete();
    }

    /**
     * Prepare the model for pruning.
     *
     * @return void
     */
    protected function pruning()
    {
        //
    }

    /**
     * Prepare the soft deletable model for soft pruning.
     *
     * @return void
     */
    protected function softPruning()
    {
        //
    }
}
