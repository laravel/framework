<?php

namespace Illuminate\Tests\Testing\Concerns;

use ErrorException;
use Illuminate\Foundation\Bootstrap\HandleExceptions;
use Illuminate\Foundation\Testing\Concerns\InteractsWithDeprecationHandling;
use PHPUnit\Framework\TestCase;

class InteractsWithDeprecationHandlingTest extends TestCase
{
    use InteractsWithDeprecationHandling;

    protected $deprecationsFound = false;

    protected function setUp(): void
    {
        parent::setUp();

        set_error_handler(function () {
            $this->deprecationsFound = true;
        });
    }

    protected function tearDown(): void
    {
        $this->deprecationsFound = false;

        HandleExceptions::flushHandlersState($this);

        parent::tearDown();
    }

    public function testWithDeprecationHandling()
    {
        $this->withDeprecationHandling();

        trigger_error('Something is deprecated', E_USER_DEPRECATED);

        $this->assertTrue($this->deprecationsFound);
    }

    public function testWithoutDeprecationHandling()
    {
        $this->withoutDeprecationHandling();

        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('Something is deprecated');

        trigger_error('Something is deprecated', E_USER_DEPRECATED);
    }
}
