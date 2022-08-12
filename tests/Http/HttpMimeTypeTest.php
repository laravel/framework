<?php

namespace Illuminate\Tests\Http;

use Illuminate\Http\Testing\MimeType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mime\MimeTypesInterface;

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

    public function testMimeTypeSymfonyInstance()
    {
        $this->assertInstanceOf(MimeTypesInterface::class, MimeType::getMimeTypes());
    }

    public function testSearchExtensionFromMimeType()
    {
        $this->assertContains(MimeType::search('video/quicktime'), ['qt', 'mov']);
        $this->assertNull(MimeType::search('foo/bar'));
    }
}
