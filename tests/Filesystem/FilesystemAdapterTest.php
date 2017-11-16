<?php

namespace Illuminate\Tests\Filesystem;

use PHPUnit\Framework\TestCase;
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local;
use Illuminate\Filesystem\FilesystemAdapter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FilesystemAdapterTest extends TestCase
{
    private $filesystem;

    public function setUp()
    {
        $this->filesystem = new Filesystem(new Local(__DIR__.'/tmp'));
    }

    public function tearDown()
    {
        $filesystem = new Filesystem(new Local(__DIR__));
        $filesystem->deleteDir('tmp');
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
}
