<?php

namespace Illuminate\Bus;

use DateTimeInterface;

interface PrunableBatchRepository extends BatchRepository
{
    /**
     * Prune all of the entries older than the given date.
     *
     * @return int
     */
    public function prune(DateTimeInterface $before);
}
