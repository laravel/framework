<?php

declare(strict_types=1);

namespace Illuminate\Contracts\Support;

interface ComputesOnceableHashInterface
{
    /**
     * Compute the hash of the onceable dependency.
     *
     * @return string
     */
    public function computeOnceableHash();
}
