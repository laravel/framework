<?php

namespace Illuminate\Tests\Integration\Concurrency;

use Illuminate\Concurrency\ProcessDriver;
use Illuminate\Foundation\Application;
use Illuminate\Process\Factory as ProcessFactory;
use Illuminate\Support\Facades\Concurrency;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;

#[RequiresOperatingSystem('Linux|DAR')]
class ConcurrencyTest extends TestCase
{
    protected function setUp(): void
    {
        $this->defineCacheRoutes(<<<PHP
<?php
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\Route;

Route::any('/concurrency', function () {
    return Concurrency::run([
        fn () => 1 + 1,
        fn () => 2 + 2,
    ]);
});
PHP);

        parent::setUp();
    }

    public function testWorkCanBeDistributed()
    {
        $response = $this->get('concurrency')
            ->assertOk();

        [$first, $second] = $response->original;

        $this->assertEquals(2, $first);
        $this->assertEquals(4, $second);
    }

    public function testRunHandlerProcessErrorCode()
    {
        $this->expectException(\Exception::class);
        $app = new Application(__DIR__);
        $processDriver = new ProcessDriver($app->make(ProcessFactory::class));
        $processDriver->run([
            fn () => exit(1),
        ]);
    }

    public function testOutputIsMappedToArrayInput()
    {
        $input = [
            'first' => fn () => 1 + 1,
            'second' => fn () => 2 + 2,
        ];

        $processOutput = Concurrency::driver('process')->run($input);

        $this->assertIsArray($processOutput);
        $this->assertArrayHasKey('first', $processOutput);
        $this->assertArrayHasKey('second', $processOutput);

        $syncOutput = Concurrency::driver('sync')->run($input);

        $this->assertIsArray($syncOutput);
        $this->assertArrayHasKey('first', $syncOutput);
        $this->assertArrayHasKey('second', $syncOutput);

        /** As of now, the spatie/fork package is not included by default.
         * $forkOutput = Concurrency::driver('fork')->run([
         * 'first' => fn() => 1 + 1,
         * 'second' => fn() => 2 + 2,
         * ]);.
         *
         * $this->assertIsArray($forkOutput);
         * $this->assertArrayHasKey('first', $forkOutput);
         * $this->assertArrayHasKey('second', $forkOutput);
         * $this->assertEquals(2, $forkOutput['first']);
         * $this->assertEquals(4, $forkOutput['second']);
         */
    }
}
