<?php

namespace Illuminate\Tests\App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\DebounceFor;
use Illuminate\Queue\InteractsWithQueue;

#[DebounceFor(30)]
class DebouncedWithCustomCacheJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, Dispatchable;

    public static $handled = false;

    public function __construct(public string $entityId)
    {
    }

    public function debounceId(): string
    {
        return $this->entityId;
    }

    public function debounceVia(): \Illuminate\Contracts\Cache\Repository
    {
        return \Illuminate\Container\Container::getInstance()
            ->make(\Illuminate\Contracts\Cache\Factory::class)
            ->store('array');
    }

    public function handle()
    {
        static::$handled = true;
    }
}
