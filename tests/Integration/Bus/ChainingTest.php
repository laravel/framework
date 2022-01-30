<?php

namespace Illuminate\Tests\Integration\Bus;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Bus;
use Orchestra\Testbench\TestCase;
use Throwable;

class ChainingTest extends TestCase
{
    protected function defineEnvironment($app)
    {
        $app->make('config')->set('queue.default', 'sync');
    }

    public function testItCanFailedTheChainUsingFailMethod()
    {
        Bus::chain([
            new FailingJob(),
        ])->catch(function (Throwable $e) {
            //
        })->dispatch();
    }
}

class FailingJob
{
    use InteractsWithQueue, Queueable;

    public function handle()
    {
        $this->fail();
    }
}
