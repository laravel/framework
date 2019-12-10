<?php

namespace Illuminate\Tests\Http;

use Illuminate\Http\InternalRedirectResponse;
use PHPUnit\Framework\TestCase;

class HttpInternalRedirectResponseTest extends TestCase
{
    public function testNameIsSetCorrectly()
    {
        $this->assertEquals('foo', (new InternalRedirectResponse('foo'))->getName());
    }
}
