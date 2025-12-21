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
            __DIR__.'/fixtures/test.txt',
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
}
