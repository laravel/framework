<?php

namespace Illuminate\Tests\Http;

use Illuminate\Http\Testing\MimeType;
use PHPUnit\Framework\TestCase;

class HttpMimeTypeTest extends TestCase
{
    public function testMimeTypeFromFileNameExistsTrue()
    {
        $this->assertSame('image/jpeg', MimeType::from('foo.jpg'));
    }

    public function testMimeTypeFromFileNameExistsFalse()
    {
        $this->assertSame('application/octet-stream', MimeType::from('foo.bar'));
    }

    public function testMimeTypeFromExtensionExistsTrue()
    {
        $this->assertSame('image/jpeg', MimeType::get('jpg'));
    }

    public function testMimeTypeFromExtensionExistsFalse()
    {
        $this->assertSame('application/octet-stream', MimeType::get('bar'));
    }

    public function testGetAllMimeTypes()
    {
        $this->assertIsArray(MimeType::get());
        $this->assertArrayHasKey('jpg', MimeType::get());
        $this->assertSame('image/jpeg', MimeType::get()['jpg']);
    }

    public function testSearchExtensionFromMimeType()
    {
        $this->assertSame('mov', MimeType::search('video/quicktime'));
        $this->assertNull(MimeType::search('foo/bar'));
    }
}
