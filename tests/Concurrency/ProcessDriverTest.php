<?php

namespace Illuminate\Tests\Concurrency;

use Illuminate\Concurrency\ProcessDriver;
use Illuminate\Foundation\Application;
use Illuminate\Process\Factory as ProcessFactory;
use PHPUnit\Framework\TestCase;

class ProcessDriverTest extends TestCase
{
    public function testRunHandlerProcessErrorCode()
    {
        $this->expectException(\Exception::class);
        $app = new Application(__DIR__);
        $processDriver = new ProcessDriver($app->make(ProcessFactory::class));
        $processDriver->run([
            fn () => exit(1),
        ]);
    }
}
