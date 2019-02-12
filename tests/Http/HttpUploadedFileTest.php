<?php

namespace Illuminate\Tests\Http;

use PHPUnit\Framework\TestCase;
use Illuminate\Http\UploadedFile;

class HttpUploadedFileTest extends TestCase
{
    public function testUploadedFileCanRetrieveContentsFromTextFile()
    {
        $file = new UploadedFile(
            __DIR__.'/fixtures/test.txt',
            'test.txt',
            null,
            null,
            null,
            true
        );

        $this->assertEquals('This is a story about something that happened long ago when your grandfather was a child.', trim($file->get()));
    }
}
