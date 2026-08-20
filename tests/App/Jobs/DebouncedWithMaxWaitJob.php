<?php

namespace Illuminate\Tests\App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\DebounceFor;
use Illuminate\Queue\InteractsWithQueue;

#[DebounceFor(30, maxWait: 60)]
class DebouncedWithMaxWaitJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, Dispatchable;

    public static $handleCount = 0;

    public function __construct(public string $entityId)
    {
    }

    public function debounceId(): string
    {
        return $this->entityId;
    }

    public function handle()
    {
        static::$handleCount++;
    }
}
