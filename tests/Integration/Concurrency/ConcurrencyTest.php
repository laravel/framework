<?php

namespace Illuminate\Tests\Integration\Console;

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
}
