<?php

namespace Illuminate\Tests\Http;

use Illuminate\Http\Testing\File as TestingFile;
use Illuminate\Http\Testing\FileFactory;
use PHPUnit\Framework\TestCase;

class HttpTestingFileFromPathTest extends TestCase
{
    public function testFromPathCreatesFileWithBasenameSizeAndMime()
    {
        $path = __DIR__.'/fixtures/test.txt';

        $file = (new FileFactory)->fromPath($path);

        $this->assertSame('test.txt', $file->getClientOriginalName());
        $this->assertSame(filesize($path), $file->getSize());
        $this->assertSame('text/plain', $file->getMimeType());
    }

    public function testFromPathAllowsOverridingNameAndMime()
    {
        $path = __DIR__.'/fixtures/test.txt';

        $file = (new FileFactory)->fromPath($path, 'custom.bin', 'application/octet-stream');

        $this->assertSame('custom.bin', $file->getClientOriginalName());
        $this->assertSame('application/octet-stream', $file->getMimeType());
    }

    public function testStaticFileFromPathConvenience()
    {
        $path = __DIR__.'/fixtures/test.txt';

        $file = TestingFile::fromPath($path);

        $this->assertSame('test.txt', $file->getClientOriginalName());
        $this->assertSame(filesize($path), $file->getSize());
        $this->assertSame('text/plain', $file->getMimeType());
    }

    public function testFromPathThrowsOnMissingFile()
    {
        $this->expectException(\LogicException::class);

        (new FileFactory)->fromPath(__DIR__.'/fixtures/does-not-exist.txt');
    }
}

