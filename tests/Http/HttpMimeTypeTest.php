<?php

namespace Illuminate\Tests\Http;

use Illuminate\Http\Testing\MimeType;
use PHPUnit\Framework\TestCase;

class HttpMimeTypeTest extends TestCase
{
    public function testMimeTypeExistsTrue()
    {
        $this->assertSame('image/jpeg', MimeType::from('foo.jpg'));
    }

    public function testMimeTypeExistsFalse()
    {
        $this->assertSame('application/octet-stream', MimeType::from('foo.bar'));
    }
}
