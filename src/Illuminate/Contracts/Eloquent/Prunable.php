<?php

namespace Illuminate\Contracts\Eloquent;

interface Prunable
{
    /**
     * Prune all prunable models in the database.
     *
     * @param  int  $chunkSize
     * @return int
     */
    public function pruneAll(int $chunkSize = 1000);

    /**
     * Get the prunable model query.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function prunable();

    /**
     * Prune the model in the database.
     *
     * @return bool|null
     */
    public function prune();
}
