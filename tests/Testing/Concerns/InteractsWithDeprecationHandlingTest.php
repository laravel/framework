<?php

namespace Illuminate\Tests\Testing\Concerns;

use ErrorException;
use Illuminate\Foundation\Testing\Concerns\InteractsWithDeprecationHandling;
use PHPUnit\Framework\TestCase;

class InteractsWithDeprecationHandlingTest extends TestCase
{
    use InteractsWithDeprecationHandling;

    protected $original;

    public function setUp(): void
    {
        parent::setUp();

        $this->original = set_error_handler(function () {
            // ..
        });
    }

    public function testWithDeprecationHandling()
    {
        $deprecated = false;

        $this->withDeprecationHandling();

        trigger_error('Something is deprecated', E_USER_DEPRECATED);

        $this->assertFalse($deprecated);
    }

    public function testWithoutDeprecationHandling()
    {
        $deprecated = false;

        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('Something is deprecated');

        $this->withoutDeprecationHandling();

        trigger_error('Something is deprecated', E_USER_DEPRECATED);
    }

    public function tearDown(): void
    {
        set_error_handler($this->original);

        $this->originalDeprecationHandler = null;

        parent::tearDown();
    }
}
