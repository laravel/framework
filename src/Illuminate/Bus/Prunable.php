<?php

namespace Illuminate\Bus;

use DateTimeInterface;

interface Prunable
{
    /**
     * Prune all of the entries older than the given date.
     *
     * @param  DateTimeInterface  $before
     * @return int
     */
    public function prune(DateTimeInterface $before);
}
