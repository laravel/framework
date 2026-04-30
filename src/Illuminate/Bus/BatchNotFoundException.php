<?php

namespace Illuminate\Bus;

use RuntimeException;

class BatchNotFoundException extends RuntimeException
{
    public function __construct(public readonly string $batchId)
    {
        parent::__construct("Batch [{$batchId}] was not found.");
    }
}
