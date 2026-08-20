<?php

namespace Illuminate\Tests\App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class FailOnExceptionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public static array $_middleware = [];

    public int $tries = 2;

    public function __construct(private $throws, public $value = null)
    {
    }

    public function handle()
    {
        throw new $this->throws;
    }

    public function middleware(): array
    {
        return self::$_middleware;
    }
}
