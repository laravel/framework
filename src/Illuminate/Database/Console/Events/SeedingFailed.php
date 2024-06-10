<?php

declare(strict_types=1);

namespace Illuminate\Database\Console\Events;

use Throwable;

class SeedingFailed
{
    protected Throwable $exception;

    public function __construct(Throwable $exception)
    {
        $this->exception = $exception;
    }

    public function exception(): Throwable
    {
        return $this->exception;
    }
}
