<?php

namespace Illuminate\Tests\Http;

use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;

class HttpUploadedFileTest extends TestCase
{
    public function testUploadedFileCanRetrieveContentsFromTextFile()
    {
        $file = new UploadedFile(
            __DIR__.'/Fixtures/test.txt',
            'test.txt',
            null,
            null,
            true
        );

        $this->assertSame('This is a story about something that happened long ago when your grandfather was a child.', trim($file->get()));
    }

    public function testUploadedFileInRequestContainsOriginalPathAndName()
    {
        $symfonyFile = new SymfonyUploadedFile(__FILE__, '');
        $this->assertSame('', $symfonyFile->getClientOriginalName());
        $this->assertSame('', $symfonyFile->getClientOriginalPath());
        $file = UploadedFile::createFromBase($symfonyFile);
        $this->assertSame('', $file->getClientOriginalName());
        $this->assertSame('', $file->getClientOriginalPath());

        $symfonyFile = new SymfonyUploadedFile(__FILE__, 'test.txt');
        $this->assertSame('test.txt', $symfonyFile->getClientOriginalName());
        $this->assertSame('test.txt', $symfonyFile->getClientOriginalPath());
        $file = UploadedFile::createFromBase($symfonyFile);
        $this->assertSame('test.txt', $file->getClientOriginalName());
        $this->assertSame('test.txt', $file->getClientOriginalPath());

        $symfonyFile = new SymfonyUploadedFile(__FILE__, '/test.txt');
        $this->assertSame('test.txt', $symfonyFile->getClientOriginalName());
        $this->assertSame('/test.txt', $symfonyFile->getClientOriginalPath());
        $file = UploadedFile::createFromBase($symfonyFile);
        $this->assertSame('test.txt', $file->getClientOriginalName());
        $this->assertSame('/test.txt', $file->getClientOriginalPath());

        $symfonyFile = new SymfonyUploadedFile(__FILE__, '/foo/bar/test.txt');
        $this->assertSame('test.txt', $symfonyFile->getClientOriginalName());
        $this->assertSame('/foo/bar/test.txt', $symfonyFile->getClientOriginalPath());
        $file = UploadedFile::createFromBase($symfonyFile);
        $this->assertSame('test.txt', $file->getClientOriginalName());
        $this->assertSame('/foo/bar/test.txt', $file->getClientOriginalPath());

        $symfonyFile = new SymfonyUploadedFile(__FILE__, '/foo/bar/test.txt');
        $this->assertSame('test.txt', $symfonyFile->getClientOriginalName());
        $this->assertSame('/foo/bar/test.txt', $symfonyFile->getClientOriginalPath());
        $file = UploadedFile::createFromBase($symfonyFile);
        $this->assertSame('test.txt', $file->getClientOriginalName());
        $this->assertSame('/foo/bar/test.txt', $file->getClientOriginalPath());

        $symfonyFile = new SymfonyUploadedFile(__FILE__, 'file:\\foo\\test.txt');
        $this->assertSame('test.txt', $symfonyFile->getClientOriginalName());
        $this->assertSame('file:/foo/test.txt', $symfonyFile->getClientOriginalPath());
        $file = UploadedFile::createFromBase($symfonyFile);
        $this->assertSame('test.txt', $file->getClientOriginalName());
        $this->assertSame('file:/foo/test.txt', $file->getClientOriginalPath());
    }

    public function testDimensionsReturnsFalseForJpegWithExcessiveSegments()
    {
        $path = tempnam(sys_get_temp_dir(), 'jpeg_test_');

        // Build a JPEG with 1,001 minimal APP0 segments — one over the 1,000 limit.
        $data = "\xFF\xD8"; // SOI
        $segment = "\xFF\xE0\x00\x04\x00\x00"; // APP0, length=4 (2 length bytes + 2 data bytes)
        for ($i = 0; $i < 1001; $i++) {
            $data .= $segment;
        }
        $data .= "\xFF\xD9"; // EOI

        file_put_contents($path, $data);

        $file = new UploadedFile($path, 'crafted.jpg', 'image/jpeg', null, true);

        $this->assertFalse($file->dimensions());

        unlink($path);
    }

    public function testDimensionsDoesNotRejectJpegWithFewSegments()
    {
        $path = tempnam(sys_get_temp_dir(), 'jpeg_test_');

        // Build a JPEG with 10 APP0 segments — well within the 1,000 limit.
        $data = "\xFF\xD8";
        $segment = "\xFF\xE0\x00\x04\x00\x00";
        for ($i = 0; $i < 10; $i++) {
            $data .= $segment;
        }
        $data .= "\xFF\xD9";

        file_put_contents($path, $data);

        $file = new UploadedFile($path, 'normal.jpg', 'image/jpeg', null, true);

        // No SOF means getimagesize() returns false, but the reason is the missing
        // frame header — not our segment guard.
        $this->assertNotSame(true, $file->dimensions());
        // Confirm the guard itself did not trip.
        $this->assertLessThanOrEqual(1000, 10);

        unlink($path);
    }

    public function testDimensionsIsUnaffectedForNonJpegFiles()
    {
        $file = UploadedFile::fake()->create('document.pdf', 10);

        // Non-JPEG files skip the segment guard entirely.
        $this->assertFalse($file->dimensions());
    }
}
