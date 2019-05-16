<?php

namespace Illuminate\Tests\Filesystem;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local;
use Illuminate\Filesystem\FilesystemAdapter;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Contracts\Filesystem\FileExistsException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class FilesystemAdapterTest extends TestCase
{
    private $tempDir;
    private $filesystem;

    protected function setUp(): void
    {
        $this->tempDir = __DIR__.'/tmp';
        $this->filesystem = new Filesystem(new Local($this->tempDir));
    }

    protected function tearDown(): void
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
        // $this->assertEquals('inline; filename="file.txt"', $response->headers->get('content-disposition'));
    }

    public function testDownload()
    {
        $this->filesystem->write('file.txt', 'Hello World');
        $files = new FilesystemAdapter($this->filesystem);
        $response = $files->download('file.txt', 'hello.txt');
        $this->assertInstanceOf(StreamedResponse::class, $response);
        // $this->assertEquals('attachment; filename="hello.txt"', $response->headers->get('content-disposition'));
    }

    public function testDownloadNonAsciiFilename()
    {
        $this->filesystem->write('file.txt', 'Hello World');
        $files = new FilesystemAdapter($this->filesystem);
        $response = $files->download('file.txt', 'пиздюк.txt');
        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertEquals('attachment; filename=pizdyuk.txt; filename*=utf-8\'\'%D0%BF%D0%B8%D0%B7%D0%B4%D1%8E%D0%BA.txt', $response->headers->get('content-disposition'));
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
        $this->assertEquals($this->tempDir.DIRECTORY_SEPARATOR.'file.txt', $filesystemAdapter->path('file.txt'));
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
        $this->assertStringEqualsFile($this->tempDir.'/file.txt', 'Hello '.PHP_EOL.'World');
    }

    public function testAppend()
    {
        file_put_contents($this->tempDir.'/file.txt', 'Hello ');
        $filesystemAdapter = new FilesystemAdapter($this->filesystem);
        $filesystemAdapter->append('file.txt', 'Moon');
        $this->assertStringEqualsFile($this->tempDir.'/file.txt', 'Hello '.PHP_EOL.'Moon');
    }

    public function testDelete()
    {
        file_put_contents($this->tempDir.'/file.txt', 'Hello World');
        $filesystemAdapter = new FilesystemAdapter($this->filesystem);
        $this->assertTrue($filesystemAdapter->delete('file.txt'));
        $this->assertFileNotExists($this->tempDir.'/file.txt');
    }

    public function testDeleteReturnsFalseWhenFileNotFound()
    {
        $filesystemAdapter = new FilesystemAdapter($this->filesystem);
        $this->assertFalse($filesystemAdapter->delete('file.txt'));
    }

    public function testCopy()
    {
        $data = '33232';
        mkdir($this->tempDir.'/foo');
        file_put_contents($this->tempDir.'/foo/foo.txt', $data);

        $filesystemAdapter = new FilesystemAdapter($this->filesystem);
        $filesystemAdapter->copy('/foo/foo.txt', '/foo/foo2.txt');

        $this->assertFileExists($this->tempDir.'/foo/foo.txt');
        $this->assertEquals($data, file_get_contents($this->tempDir.'/foo/foo.txt'));

        $this->assertFileExists($this->tempDir.'/foo/foo2.txt');
        $this->assertEquals($data, file_get_contents($this->tempDir.'/foo/foo2.txt'));
    }

    public function testMove()
    {
        $data = '33232';
        mkdir($this->tempDir.'/foo');
        file_put_contents($this->tempDir.'/foo/foo.txt', $data);

        $filesystemAdapter = new FilesystemAdapter($this->filesystem);
        $filesystemAdapter->move('/foo/foo.txt', '/foo/foo2.txt');

        $this->assertFileNotExists($this->tempDir.'/foo/foo.txt');

        $this->assertFileExists($this->tempDir.'/foo/foo2.txt');
        $this->assertEquals($data, file_get_contents($this->tempDir.'/foo/foo2.txt'));
    }

    public function testStream()
    {
        $this->filesystem->write('file.txt', $original_content = 'Hello World');
        $filesystemAdapter = new FilesystemAdapter($this->filesystem);
        $readStream = $filesystemAdapter->readStream('file.txt');
        $filesystemAdapter->writeStream('copy.txt', $readStream);
        $this->assertEquals($original_content, $filesystemAdapter->get('copy.txt'));
    }

    public function testStreamBetweenFilesystems()
    {
        $secondFilesystem = new Filesystem(new Local($this->tempDir.'/second'));
        $this->filesystem->write('file.txt', $original_content = 'Hello World');
        $filesystemAdapter = new FilesystemAdapter($this->filesystem);
        $secondFilesystemAdapter = new FilesystemAdapter($secondFilesystem);
        $readStream = $filesystemAdapter->readStream('file.txt');
        $secondFilesystemAdapter->writeStream('copy.txt', $readStream);
        $this->assertEquals($original_content, $secondFilesystemAdapter->get('copy.txt'));
    }

    public function testStreamToExistingFileThrows()
    {
        $this->expectException(FileExistsException::class);
        $this->filesystem->write('file.txt', 'Hello World');
        $this->filesystem->write('existing.txt', 'Dear Kate');
        $filesystemAdapter = new FilesystemAdapter($this->filesystem);
        $readStream = $filesystemAdapter->readStream('file.txt');
        $filesystemAdapter->writeStream('existing.txt', $readStream);
    }

    public function testReadStreamNonExistentFileThrows()
    {
        $this->expectException(FileNotFoundException::class);
        $filesystemAdapter = new FilesystemAdapter($this->filesystem);
        $filesystemAdapter->readStream('nonexistent.txt');
    }

    public function testStreamInvalidResourceThrows()
    {
        $this->expectException(InvalidArgumentException::class);
        $filesystemAdapter = new FilesystemAdapter($this->filesystem);
        $filesystemAdapter->writeStream('file.txt', 'foo bar');
    }
}
