<?php

namespace Illuminate\Filesystem;

use RuntimeException;

class StrayDiskUsageException extends RuntimeException
{
    public function __construct(string $disk)
    {
        parent::__construct('Attempted to use disk ['.$disk.'] without a matching fake.');
    }
}
