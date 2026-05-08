<?php

namespace Illuminate\Tests\Mail;

use Illuminate\Mail\Attachment;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class AttachmentTest extends TestCase
{
    public function testFromUrlWithHttpScheme(): void
    {
        $attachment = Attachment::fromUrl('http://example.com/file.pdf');

        $this->assertInstanceOf(Attachment::class, $attachment);
    }

    public function testFromUrlWithHttpsScheme(): void
    {
        $attachment = Attachment::fromUrl('https://example.com/file.pdf');

        $this->assertInstanceOf(Attachment::class, $attachment);
    }

    public function testFromUrlThrowsForFtpScheme(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Attachment URLs must use the http or https scheme.');

        Attachment::fromUrl('ftp://example.com/file.pdf');
    }

    public function testFromUrlThrowsForFileScheme(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Attachment URLs must use the http or https scheme.');

        Attachment::fromUrl('file:///var/www/file.pdf');
    }

    public function testFromUrlThrowsForMailtoScheme(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Attachment URLs must use the http or https scheme.');

        Attachment::fromUrl('mailto:user@example.com');
    }

    public function testFromUrlThrowsForInvalidUrl(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Attachment URLs must use the http or https scheme.');

        Attachment::fromUrl('not-a-url');
    }

    public function testFromUrlThrowsForEmptyString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Attachment URLs must use the http or https scheme.');

        Attachment::fromUrl('');
    }

    public function testAsSetFilename(): void
    {
        $attachment = Attachment::fromPath('/path/to/file.pdf')
            ->as('renamed.pdf');

        $this->assertSame('renamed.pdf', $attachment->as);
    }

    public function testWithMimeSetsMimeType(): void
    {
        $attachment = Attachment::fromPath('/path/to/file.pdf')
            ->withMime('application/pdf');

        $this->assertSame('application/pdf', $attachment->mime);
    }

    public function testFluentChaining(): void
    {
        $attachment = Attachment::fromPath('/path/to/file.jpg')
            ->as('photo.jpg')
            ->withMime('image/jpeg');

        $this->assertSame('photo.jpg', $attachment->as);
        $this->assertSame('image/jpeg', $attachment->mime);
    }

    public function testIsEquivalentWithSamePath(): void
    {
        $a = Attachment::fromPath('/path/to/file.pdf')->as('file.pdf');
        $b = Attachment::fromPath('/path/to/file.pdf')->as('file.pdf');

        $this->assertTrue($a->isEquivalent($b));
    }

    public function testIsEquivalentWithDifferentPaths(): void
    {
        $a = Attachment::fromPath('/path/to/a.pdf');
        $b = Attachment::fromPath('/path/to/b.pdf');

        $this->assertFalse($a->isEquivalent($b));
    }

    public function testFromDataCreatesAttachment(): void
    {
        $attachment = Attachment::fromData(fn () => 'file content', 'report.txt');

        $this->assertInstanceOf(Attachment::class, $attachment);
        $this->assertSame('report.txt', $attachment->as);
    }
}
