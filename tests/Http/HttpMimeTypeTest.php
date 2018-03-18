<?php

namespace Illuminate\Tests\Http;

use PHPUnit\Framework\TestCase;
use Illuminate\Http\Testing\MimeType;

class HttpMimeTypeTest extends TestCase
{
    public function testMimeTypeFromFileNameExistsTrue(): void
    {
        $this->assertSame('image/jpeg', MimeType::from('foo.jpg'));
    }

    public function testMimeTypeFromFileNameExistsFalse(): void
    {
        $this->assertSame('application/octet-stream', MimeType::from('foo.bar'));
    }

    public function testMimeTypeFromExtensionExistsTrue(): void
    {
        $this->assertSame('image/jpeg', MimeType::get('jpg'));
    }

    public function testMimeTypeFromExtensionExistsFalse(): void
    {
        $this->assertSame('application/octet-stream', MimeType::get('bar'));
    }

    public function testGetAllMimeTypes(): void
    {
        $this->assertInternalType('array', MimeType::get());
        $this->assertArraySubset(['jpg' => 'image/jpeg'], MimeType::get());
    }

    public function testSearchExtensionFromMimeType(): void
    {
        $this->assertSame('mov', MimeType::search('video/quicktime'));
        $this->assertNull(MimeType::search('foo/bar'));
    }
}
