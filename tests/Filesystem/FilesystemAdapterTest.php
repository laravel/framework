<?php

namespace Illuminate\Tests\Filesystem;

use Carbon\Carbon;
use GuzzleHttp\Psr7\Stream;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Foundation\Application;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\Assert;
use InvalidArgumentException;
use League\Flysystem\Filesystem;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToWriteFile;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FilesystemAdapterTest extends TestCase
{
    private $tempDir;
    private $filesystem;
    private $adapter;

    protected function setUp(): void
    {
        $this->tempDir = __DIR__.'/tmp';
        $this->filesystem = new Filesystem(
            $this->adapter = new LocalFilesystemAdapter($this->tempDir)
        );
    }

    protected function tearDown(): void
    {
        $filesystem = new Filesystem(
            $this->adapter = new LocalFilesystemAdapter(dirname($this->tempDir))
        );
        $filesystem->deleteDirectory(basename($this->tempDir));
        m::close();

        unset($this->tempDir, $this->filesystem, $this->adapter);
    }

    public function testResponse()
    {
        $this->filesystem->write('file.txt', 'Hello World');
        $files = new FilesystemAdapter($this->filesystem, $this->adapter);
        $response = $files->response('file.txt');

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertSame('Hello World', $content);
        $this->assertSame('inline; filename=file.txt', $response->headers->get('content-disposition'));
    }

    public function testDownload()
    {
        $this->filesystem->write('file.txt', 'Hello World');
        $files = new FilesystemAdapter($this->filesystem, $this->adapter);
        $response = $files->download('file.txt', 'hello.txt');
        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertSame('attachment; filename=hello.txt', $response->headers->get('content-disposition'));
    }

    public function testDownloadNonAsciiFilename()
    {
        $this->filesystem->write('file.txt', 'Hello World');
        $files = new FilesystemAdapter($this->filesystem, $this->adapter);
        $response = $files->download('file.txt', 'пиздюк.txt');
        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertSame("attachment; filename=pizdiuk.txt; filename*=utf-8''%D0%BF%D0%B8%D0%B7%D0%B4%D1%8E%D0%BA.txt", $response->headers->get('content-disposition'));
    }

    public function testDownloadNonAsciiEmptyFilename()
    {
        $this->filesystem->write('пиздюк.txt', 'Hello World');
        $files = new FilesystemAdapter($this->filesystem, $this->adapter);
        $response = $files->download('пиздюк.txt');
        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertSame('attachment; filename=pizdiuk.txt; filename*=utf-8\'\'%D0%BF%D0%B8%D0%B7%D0%B4%D1%8E%D0%BA.txt', $response->headers->get('content-disposition'));
    }

    public function testDownloadPercentInFilename()
    {
        $this->filesystem->write('Hello%World.txt', 'Hello World');
        $files = new FilesystemAdapter($this->filesystem, $this->adapter);
        $response = $files->download('Hello%World.txt', 'Hello%World.txt');
        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertSame('attachment; filename=HelloWorld.txt; filename*=utf-8\'\'Hello%25World.txt', $response->headers->get('content-disposition'));
    }

    public function testExists()
    {
        $this->filesystem->write('file.txt', 'Hello World');
        $filesystemAdapter = new FilesystemAdapter($this->filesystem, $this->adapter);
        $this->assertTrue($filesystemAdapter->exists('file.txt'));
        $this->assertTrue($filesystemAdapter->fileExists('file.txt'));
    }

    public function testMissing()
    {
        $filesystemAdapter = new FilesystemAdapter($this->filesystem, $this->adapter);
        $this->assertTrue($filesystemAdapter->missing('file.txt'));
        $this->assertTrue($filesystemAdapter->fileMissing('file.txt'));
    }

    public function testDirectoryExists()
    {
        $this->filesystem->write('/foo/bar/file.txt', 'Hello World');
        $filesystemAdapter = new FilesystemAdapter($this->filesystem, $this->adapter);
        $this->assertTrue($filesystemAdapter->directoryExists('/foo/bar'));
    }

    public function testDirectoryMissing()
    {
        $filesystemAdapter = new FilesystemAdapter($this->filesystem, $this->adapter);
        $this->assertTrue($filesystemAdapter->directoryMissing('/foo/bar'));
    }

    public function testPath()
    {
        $this->filesystem->write('file.txt', 'Hello World');
        $filesystemAdapter = new FilesystemAdapter($this->filesystem, $this->adapter, [
            'root' => $this->tempDir.DIRECTORY_SEPARATOR,
        ]);
        $this->assertEquals($this->tempDir.DIRECTORY_SEPARATOR.'file.txt', $filesystemAdapter->path('file.txt'));
    }

    public function testGet()
    {
        $this->filesystem->write('file.txt', 'Hello World');
        $filesystemAdapter = new FilesystemAdapter($this->filesystem, $this->adapter);
        $this->assertSame('Hello World', $filesystemAdapter->get('file.txt'));
    }

    public function testGetFileNotFound()
    {
        $filesystemAdapter = new FilesystemAdapter($this->filesystem, $this->adapter);
        $this->assertNull($filesystemAdapter->get('file.txt'));
    }

    public function testPut()
    {
        $filesystemAdapter = new FilesystemAdapter($this->filesystem, $this->adapter);
        $filesystemAdapter->put('file.txt', 'Something inside');
        $this->assertStringEqualsFile($this->tempDir.'/file.txt', 'Something inside');
    }

    public function testPrepend()
    {
        file_put_contents($this->tempDir.'/file.txt', 'World');
        $filesystemAdapter = new FilesystemAdapter($this->filesystem, $this->adapter);
        $filesystemAdapter->prepend('file.txt', 'Hello ');
        $this->assertStringEqualsFile($this->tempDir.'/file.txt', 'Hello '.PHP_EOL.'World');
    }

    public function testAppend()
    {
        file_put_contents($this->tempDir.'/file.txt', 'Hello ');
        $filesystemAdapter = new FilesystemAdapter($this->filesystem, $this->adapter);
        $filesystemAdapter->append('file.txt', 'Moon');
        $this->assertStringEqualsFile($this->tempDir.'/file.txt', 'Hello '.PHP_EOL.'Moon');
    }

    public function testDelete()
    {
        file_put_contents($this->tempDir.'/file.txt', 'Hello World');
        $filesystemAdapter = new FilesystemAdapter($this->filesystem, $this->adapter);
        $this->assertTrue($filesystemAdapter->delete('file.txt'));
        Assert::assertFileDoesNotExist($this->tempDir.'/file.txt');
    }

    public function testDeleteReturnsTrueWhenFileNotFound()
    {
        $filesystemAdapter = new FilesystemAdapter($this->filesystem, $this->adapter);
        $this->assertTrue($filesystemAdapter->delete('file.txt'));
    }

    public function testCopy()
    {
        $data = '33232';
        mkdir($this->tempDir.'/foo');
        file_put_contents($this->tempDir.'/foo/foo.txt', $data);

        $filesystemAdapter = new FilesystemAdapter($this->filesystem, $this->adapter);
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

        $filesystemAdapter = new FilesystemAdapter($this->filesystem, $this->adapter);
        $filesystemAdapter->move('/foo/foo.txt', '/foo/foo2.txt');

        Assert::assertFileDoesNotExist($this->tempDir.'/foo/foo.txt');

        $this->assertFileExists($this->tempDir.'/foo/foo2.txt');
        $this->assertEquals($data, file_get_contents($this->tempDir.'/foo/foo2.txt'));
    }

    public function testStream()
    {
        $this->filesystem->write('file.txt', $original_content = 'Hello World');
        $filesystemAdapter = new FilesystemAdapter($this->filesystem, $this->adapter);
        $readStream = $filesystemAdapter->readStream('file.txt');
        $filesystemAdapter->writeStream('copy.txt', $readStream);
        $this->assertEquals($original_content, $filesystemAdapter->get('copy.txt'));
    }

    public function testStreamBetweenFilesystems()
    {
        $secondFilesystem = new Filesystem(new LocalFilesystemAdapter($this->tempDir.'/second'));
        $this->filesystem->write('file.txt', $original_content = 'Hello World');
        $filesystemAdapter = new FilesystemAdapter($this->filesystem, $this->adapter);
        $secondFilesystemAdapter = new FilesystemAdapter($secondFilesystem, $this->adapter);
        $readStream = $filesystemAdapter->readStream('file.txt');
        $secondFilesystemAdapter->writeStream('copy.txt', $readStream);
        $this->assertEquals($original_content, $secondFilesystemAdapter->get('copy.txt'));
    }

    public function testStreamToExistingFileOverwrites()
    {
        $this->filesystem->write('file.txt', 'Hello World');
        $this->filesystem->write('existing.txt', 'Dear Kate');
        $filesystemAdapter = new FilesystemAdapter($this->filesystem, $this->adapter);
        $readStream = $filesystemAdapter->readStream('file.txt');
        $filesystemAdapter->writeStream('existing.txt', $readStream);
        $this->assertSame('Hello World', $filesystemAdapter->read('existing.txt'));
    }

    public function testReadStreamNonExistentFileReturnsNull()
    {
        $filesystemAdapter = new FilesystemAdapter($this->filesystem, $this->adapter);
        $this->assertNull($filesystemAdapter->readStream('nonexistent.txt'));
    }

    public function testStreamInvalidResourceThrows()
    {
        $this->expectException(InvalidArgumentException::class);
        $filesystemAdapter = new FilesystemAdapter($this->filesystem, $this->adapter);
        $filesystemAdapter->writeStream('file.txt', 'foo bar');
    }

    public function testPutWithStreamInterface()
    {
        file_put_contents($this->tempDir.'/foo.txt', 'some-data');

        $filesystemAdapter = new FilesystemAdapter($this->filesystem, $this->adapter);
        $stream = fopen($this->tempDir.'/foo.txt', 'r');
        $guzzleStream = new Stream($stream);
        $filesystemAdapter->put('bar.txt', $guzzleStream);
        fclose($stream);

        $this->assertSame('some-data', $filesystemAdapter->get('bar.txt'));
    }

    public function testPutFileAs()
    {
        file_put_contents($filePath = $this->tempDir.'/foo.txt', 'uploaded file content');

        $filesystemAdapter = new FilesystemAdapter($this->filesystem, $this->adapter);

        $uploadedFile = new UploadedFile($filePath, 'org.txt', null, null, true);

        $storagePath = $filesystemAdapter->putFileAs('/', $uploadedFile, 'new.txt');

        $this->assertSame('new.txt', $storagePath);

        $this->assertFileExists($filePath);

        $filesystemAdapter->assertExists($storagePath);

        $this->assertSame('uploaded file content', $filesystemAdapter->read($storagePath));

        $filesystemAdapter->assertExists(
            $storagePath,
            'uploaded file content'
        );
    }

    public function testPutFileAsWithAbsoluteFilePath()
    {
        file_put_contents($filePath = $this->tempDir.'/foo.txt', 'normal file content');

        $filesystemAdapter = new FilesystemAdapter($this->filesystem, $this->adapter);

        $storagePath = $filesystemAdapter->putFileAs('/', $filePath, 'new.txt');

        $this->assertSame('normal file content', $filesystemAdapter->read($storagePath));
    }

    public function testPutFile()
    {
        file_put_contents($filePath = $this->tempDir.'/foo.txt', 'uploaded file content');

        $filesystemAdapter = new FilesystemAdapter($this->filesystem, $this->adapter);

        $uploadedFile = new UploadedFile($filePath, 'org.txt', null, null, true);

        $storagePath = $filesystemAdapter->putFile('/', $uploadedFile);

        $this->assertSame(44, strlen($storagePath)); // random 40 characters + ".txt"

        $this->assertFileExists($filePath);

        $filesystemAdapter->assertExists($storagePath);

        $filesystemAdapter->assertExists(
            $storagePath,
            'uploaded file content'
        );
    }

    public function testPutFileWithAbsoluteFilePath()
    {
        file_put_contents($filePath = $this->tempDir.'/foo.txt', 'uploaded file content');

        $filesystemAdapter = new FilesystemAdapter($this->filesystem, $this->adapter);

        $storagePath = $filesystemAdapter->putFile('/', $filePath);

        $this->assertSame(44, strlen($storagePath)); // random 40 characters + ".txt"

        $filesystemAdapter->assertExists($storagePath);

        $filesystemAdapter->assertExists(
            $storagePath,
            'uploaded file content'
        );
    }

    /**
     * @requires extension ftp
     */
    public function testCreateFtpDriver()
    {
        $filesystem = new FilesystemManager(new Application);

        $driver = $filesystem->createFtpDriver([
            'host' => 'ftp.example.com',
            'username' => 'admin',
            'permPublic' => 0700,
            'unsupportedParam' => true,
        ]);

        $this->assertInstanceOf(FtpAdapter::class, $driver->getAdapter());

        $config = $driver->getConfig();
        $this->assertEquals(0700, $config['permPublic']);
        $this->assertSame('ftp.example.com', $config['host']);
        $this->assertSame('admin', $config['username']);
    }

    public function testMacroable()
    {
        $this->filesystem->write('foo.txt', 'Hello World');

        $filesystemAdapter = new FilesystemAdapter($this->filesystem, $this->adapter);
        $filesystemAdapter->macro('getFoo', function () {
            return $this->get('foo.txt');
        });

        $this->assertSame('Hello World', $filesystemAdapter->getFoo());
    }

    public function testTemporaryUrlWithCustomCallback()
    {
        $filesystemAdapter = new FilesystemAdapter($this->filesystem, $this->adapter);

        $filesystemAdapter->buildTemporaryUrlsUsing(function ($path, Carbon $expiration, $options) {
            return $path.$expiration->toString().implode('', $options);
        });

        $path = 'foo';
        $expiration = Carbon::create(2021, 18, 12, 13);
        $options = ['bar' => 'baz'];

        $this->assertSame(
            $path.$expiration->toString().implode('', $options),
            $filesystemAdapter->temporaryUrl($path, $expiration, $options)
        );
    }

    public function testThrowExceptionsForGet()
    {
        $adapter = new FilesystemAdapter($this->filesystem, $this->adapter, ['throw' => true]);

        try {
            $adapter->get('/foo.txt');
        } catch (UnableToReadFile $e) {
            $this->assertTrue(true);

            return;
        }

        $this->fail('Exception was not thrown.');
    }

    public function testThrowExceptionsForReadStream()
    {
        $adapter = new FilesystemAdapter($this->filesystem, $this->adapter, ['throw' => true]);

        try {
            $adapter->readStream('/foo.txt');
        } catch (UnableToReadFile $e) {
            $this->assertTrue(true);

            return;
        }

        $this->fail('Exception was not thrown.');
    }

    public function testThrowExceptionsForPut()
    {
        $this->filesystem->write('foo.txt', 'Hello World');

        chmod(__DIR__.'/tmp/foo.txt', 0400);

        $adapter = new FilesystemAdapter($this->filesystem, $this->adapter, ['throw' => true]);

        try {
            $adapter->put('/foo.txt', 'Hello World!');
        } catch (UnableToWriteFile $e) {
            $this->assertTrue(true);

            return;
        } finally {
            chmod(__DIR__.'/tmp/foo.txt', 0600);
        }

        $this->fail('Exception was not thrown.');
    }
}
