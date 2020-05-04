<?php

namespace Illuminate\Tests\Http;

use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\TestCase;

class HttpUploadedFileTest extends TestCase
{
    public function testUploadedFileCanRetrieveContentsFromTextFile()
    {
        $file = new UploadedFile(
            __DIR__.'/fixtures/test.txt',
            'test.txt',
            null,
            null,
            true
        );

        $this->assertSame('This is a story about something that happened long ago when your grandfather was a child.', trim($file->get()));
    }

    public function testUploadedFileHashName()
    {
        $fileWithoutExtension = new UploadedFile(
            __DIR__.'/fixtures/test',
            'test',
            null,
            null,
            true
        );

        $fileWithKnownExtension = new UploadedFile(
            __DIR__.'/fixtures/test.txt',
            'test.txt',
            null,
            null,
            true
        );

        $fileWithWrongExtension = new UploadedFile(
            __DIR__.'/fixtures/test.abc',
            'test.abc',
            null,
            null,
            true
        );

        $fileWithUnknownExtension = new UploadedFile(
            __DIR__.'/fixtures/test.unknown',
            'test.unknown',
            null,
            null,
            true
        );

        $this->assertRegExp('/^[a-z0-9]{40}$/i', $fileWithoutExtension->hashName());
        $this->assertRegExp('/^[a-z0-9]{40}.txt$/i', $fileWithKnownExtension->hashName());
        $this->assertRegExp('/^[a-z0-9]{40}.txt$/i', $fileWithWrongExtension->hashName());
        $this->assertRegExp('/^[a-z0-9]{40}.unknown/i', $fileWithUnknownExtension->hashName());
    }
}
