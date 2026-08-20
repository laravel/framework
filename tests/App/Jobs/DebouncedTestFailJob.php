<?php

namespace Illuminate\Tests\App\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\DebounceFor;
use Illuminate\Queue\InteractsWithQueue;

#[DebounceFor(30)]
class DebouncedTestFailJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, Dispatchable;

    public $tries = 1;

    public static $handled = false;

    public function __construct(public string $entityId)
    {
    }

    public function debounceId(): string
    {
        return $this->entityId;
    }

    public function handle()
    {
        static::$handled = true;

        throw new Exception;
    }
}
