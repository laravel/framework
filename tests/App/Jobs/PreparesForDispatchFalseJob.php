<?php

namespace Illuminate\Tests\App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\PreparesForDispatch;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class PreparesForDispatchFalseJob implements PreparesForDispatch, ShouldQueue
{
    use Dispatchable, Queueable;

    public function prepareForDispatch(): bool
    {
        return false;
    }

    public function handle(): void
    {
    }
}
