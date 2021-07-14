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
        // Regression: check for both "qt" & "mov" because of a behavioral change in Symfony 5.3
        // See: https://github.com/symfony/symfony/pull/41016
        $this->assertContains(MimeType::search('video/quicktime'), ['qt', 'mov']);
        $this->assertNull(MimeType::search('foo/bar'));
    }
}
