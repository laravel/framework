<?php

namespace Illuminate\Tests\Filesystem;

use PHPUnit\Framework\TestCase;
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local;
use Illuminate\Filesystem\FilesystemAdapter;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class FilesystemAdapterTest extends TestCase
{
    private $filesystem;

    public function setUp()
    {
        $this->tempDir = __DIR__.'/tmp';
        $this->filesystem = new Filesystem(new Local($this->tempDir));
    }

    public function tearDown()
    {
        $filesystem = new Filesystem(new Local(dirname($this->tempDir)));
        $filesystem->deleteDir(basename($this->tempDir));
    }

    public function testResponse()
    {
        $this->filesystem->write('file.txt', 'Hello World');
        $files = new FilesystemAdapter($this->filesystem);
        $response = $files->response('file.txt');

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertEquals('Hello World', $content);
        $this->assertEquals('inline; filename="file.txt"', $response->headers->get('content-disposition'));
    }

    public function testDownload()
    {
        $this->filesystem->write('file.txt', 'Hello World');
        $files = new FilesystemAdapter($this->filesystem);
        $response = $files->download('file.txt', 'hello.txt');
        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertEquals('attachment; filename="hello.txt"', $response->headers->get('content-disposition'));
    }

    public function testExists()
    {
        $this->filesystem->write('file.txt', 'Hello World');
        $filesystemAdapter = new FilesystemAdapter($this->filesystem);
        $this->assertTrue($filesystemAdapter->exists('file.txt'));
    }

    public function testPath()
    {
        $this->filesystem->write('file.txt', 'Hello World');
        $filesystemAdapter = new FilesystemAdapter($this->filesystem);
        $this->assertEquals($this->tempDir.'/file.txt', $filesystemAdapter->path('file.txt'));
    }

    public function testGet()
    {
        $this->filesystem->write('file.txt', 'Hello World');
        $filesystemAdapter = new FilesystemAdapter($this->filesystem);
        $this->assertEquals('Hello World', $filesystemAdapter->get('file.txt'));
    }

    public function testGetFileNotFound()
    {
        $filesystemAdapter = new FilesystemAdapter($this->filesystem);
        $this->expectException(FileNotFoundException::class);
        $filesystemAdapter->get('file.txt');
    }

    public function testPut()
    {
        $filesystemAdapter = new FilesystemAdapter($this->filesystem);
        $filesystemAdapter->put('file.txt', 'Something inside');
        $this->assertStringEqualsFile($this->tempDir.'/file.txt', 'Something inside');
    }

    public function testPrepend()
    {
        file_put_contents($this->tempDir.'/file.txt', 'World');
        $filesystemAdapter = new FilesystemAdapter($this->filesystem);
        $filesystemAdapter->prepend('file.txt', 'Hello ');
        $this->assertStringEqualsFile($this->tempDir.'/file.txt', "Hello \nWorld");
    }

    public function testAppend()
    {
        file_put_contents($this->tempDir.'/file.txt', 'Hello ');
        $filesystemAdapter = new FilesystemAdapter($this->filesystem);
        $filesystemAdapter->append('file.txt', 'Moon');
        $this->assertStringEqualsFile($this->tempDir.'/file.txt', "Hello \nMoon");
    }
}
