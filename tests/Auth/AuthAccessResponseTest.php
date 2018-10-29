<?php

namespace Illuminate\Tests\Auth;

use Illuminate\Auth\Access\Response;
use PHPUnit\Framework\TestCase;

class AuthAccessResponseTest extends TestCase
{
    /**
     * @return void
     */
    public function testStringMethodWillReturnString()
    {
        $response = new Response('some data');
        $this->assertSame('some data', (string) $response);

        $response = new Response();
        $this->assertSame('', (string) $response);
    }
}
