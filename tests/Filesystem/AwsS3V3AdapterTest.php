<?php

namespace Illuminate\Tests\Filesystem;

use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Foundation\Application;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class AwsS3V3AdapterTest extends TestCase
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

    public function test_process_file_using_callback_returns_expected_values_for_s3_adapter_with_zip_file()
    {
        $filesystem = new FilesystemManager(new Application);

        $filesystemAdapter = $filesystem->createS3Driver([
            'region' => 'us-west-1',
            'bucket' => 'laravel',
        ]);

        $receivedPath = null;

        $result = $filesystemAdapter->processFileUsing('archive.zip', function ($path) use (&$receivedPath) {
            $receivedPath = $path;

            return 'processed-zip';
        });

        $this->assertSame('processed-zip', $result);

        $this->assertNotNull($receivedPath);
        $this->assertStringEndsWith('archive.zip', $receivedPath);
    }

    public function test_get_copies_zip_file_from_s3_to_local_and_returns_contents()
    {
        /** @var AwsS3V3Adapter|m\MockInterface $adapter */
        $adapter = m::mock(AwsS3V3Adapter::class)->makePartial();

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, 'zip-contents');
        rewind($stream);

        $adapter->shouldReceive('readStream')
            ->once()
            ->with('archive.zip')
            ->andReturn($stream);

        $adapter->shouldReceive('get')->passthru()->byDefault();

        $result = $adapter->get('archive.zip');

        $this->assertSame('zip-contents', $result);
    }

    public function test_get_falls_back_to_parent_get_for_non_zip_files()
    {
        /** @var AwsS3V3Adapter|m\MockInterface $adapter */
        $filesystemAdapter = new FilesystemAdapter($this->filesystem, $this->adapter);
        $adapter = $this->createConfiguredMock(AwsS3V3Adapter::class, [
            'get' => $filesystemAdapter->get('file.txt'),
        ]);

        $this->assertNull($adapter->get('file.txt'));
    }

    public function test_path_copies_zip_file_from_s3_to_local_and_returns_original_path()
    {
        /** @var AwsS3V3Adapter|m\MockInterface $adapter */
        $adapter = m::mock(AwsS3V3Adapter::class)->makePartial();

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, 'zip-contents');
        rewind($stream);

        $adapter->shouldReceive('readStream')
            ->once()
            ->with('archive.zip')
            ->andReturn($stream);

        $adapter->shouldReceive('path')->passthru()->byDefault();

        $result = $adapter->path('archive.zip');

        $this->assertSame('archive.zip', $result);
    }

    public function test_path_falls_back_to_parent_path_for_non_zip_files()
    {
        $filesystem = new Filesystem(
            $this->adapter = new LocalFilesystemAdapter($this->tempDir)
        );

        $filesystemAdapter = new FilesystemAdapter($filesystem, $this->adapter, [
            'root' => $this->tempDir.DIRECTORY_SEPARATOR,
        ]);

        $this->filesystem->write('file.txt', 'Hello World');

        $this->assertEquals(
            $this->tempDir.DIRECTORY_SEPARATOR.'file.txt',
            $filesystemAdapter->path('file.txt')
        );
    }
}
