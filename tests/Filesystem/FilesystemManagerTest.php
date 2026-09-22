<?php

namespace Illuminate\Tests\Filesystem;

use Aws\Exception\CredentialsException;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Filesystem\Filesystem as FilesystemContract;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Foundation\Application;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use League\Flysystem\UnableToReadFile;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;
use PHPUnit\Framework\TestCase;
use stdClass;

class FilesystemManagerTest extends TestCase
{
    protected array $temporaryDirectories = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryDirectories as $directory) {
            (new Filesystem)->deleteDirectory($directory);
        }

        parent::tearDown();
    }

    public function testEcsS3DiskUsesExplicitEndpointAndIgnoresStaticCredentials()
    {
        $environment = [
            'AWS_ACCESS_KEY_ID' => 'ambient-r2-key',
            'AWS_SECRET_ACCESS_KEY' => 'ambient-r2-secret',
            'AWS_ENDPOINT_URL' => 'https://r2.example.com',
            'AWS_ENDPOINT_URL_S3' => 'https://r2.example.com',
            'AWS_CONTAINER_CREDENTIALS_RELATIVE_URI' => '',
            'AWS_CONTAINER_CREDENTIALS_FULL_URI' => 'http://192.0.2.1/credentials',
        ];
        $previous = [];

        foreach ($environment as $key => $value) {
            $previous[$key] = getenv($key);
            putenv($key.'='.$value);
        }

        try {
            $disk = (new FilesystemManager(new Application))->build([
                'driver' => 's3',
                'credentials' => 'ecs',
                'region' => 'us-east-2',
                'bucket' => 'arn:aws:s3:us-east-2:123456789012:accesspoint/environment-bucket',
                'key' => 'disk-r2-key',
                'secret' => 'disk-r2-secret',
                'endpoint' => 'https://s3-accesspoint.us-east-2.amazonaws.com',
            ]);

            $client = $disk->getClient();
            $this->assertSame('us-east-2', $client->getRegion());
            $this->assertSame('https://s3-accesspoint.us-east-2.amazonaws.com', (string) $client->getEndpoint());

            // An invalid container host fails before HTTP, rather than falling back to R2 keys.
            $this->expectException(CredentialsException::class);
            $this->expectExceptionMessage('unsupported host');
            $client->getCredentials()->wait();
        } finally {
            foreach ($previous as $key => $value) {
                putenv($value === false ? $key : $key.'='.$value);
            }
        }
    }

    public function testS3DisksRouteAccessPointsAndR2ToTheirOwnEndpoints()
    {
        $previous = getenv('AWS_ENDPOINT_URL');
        putenv('AWS_ENDPOINT_URL=https://ambient.example.com');
        $manager = new FilesystemManager(new Application);

        try {
            $aws = $manager->build([
                'driver' => 's3',
                'region' => 'us-east-2',
                'endpoint' => 'https://s3-accesspoint.us-east-2.amazonaws.com',
                'bucket' => 'arn:aws:s3:us-east-2:123456789012:accesspoint/environment-bucket',
                'credentials' => false,
            ]);
            $r2 = $manager->build([
                'driver' => 's3',
                'region' => 'auto',
                'endpoint' => 'https://account.r2.cloudflarestorage.com',
                'bucket' => 'archive',
                'key' => 'r2-key',
                'secret' => 'r2-secret',
            ]);

            // Serialize requests without sending them to either provider.
            foreach (['GetObject', 'PutObject', 'DeleteObject'] as $operation) {
                $awsRequest = \Aws\serialize($aws->getClient()->getCommand($operation, [
                    'Bucket' => $aws->getConfig()['bucket'], 'Key' => 'hello.txt',
                ]));
                $r2Request = \Aws\serialize($r2->getClient()->getCommand($operation, [
                    'Bucket' => $r2->getConfig()['bucket'], 'Key' => 'hello.txt',
                ]));

                $this->assertSame('https://environment-bucket-123456789012.s3-accesspoint.us-east-2.amazonaws.com/hello.txt', (string) $awsRequest->getUri());
                $this->assertSame('https://archive.account.r2.cloudflarestorage.com/hello.txt', (string) $r2Request->getUri());
            }
        } finally {
            putenv($previous === false ? 'AWS_ENDPOINT_URL' : 'AWS_ENDPOINT_URL='.$previous);
        }
    }

    public function testS3CredentialProviderOptionsAndMemoization()
    {
        $previous = getenv('AWS_CONTAINER_CREDENTIALS_RELATIVE_URI');
        putenv('AWS_CONTAINER_CREDENTIALS_RELATIVE_URI=/credentials');
        $requests = 0;

        try {
            $config = $this->s3Config([
                'credentials' => [
                    'provider' => 'ecs',
                    'timeout' => 7,
                    'client' => function ($request, $options) use (&$requests) {
                        $requests++;
                        $this->assertEquals(7, $options['timeout']);

                        return Create::promiseFor(new Response(200, [], json_encode([
                            'AccessKeyId' => 'container-key',
                            'SecretAccessKey' => 'container-secret',
                            'Token' => 'container-token',
                            'Expiration' => gmdate('c', time() + 3600),
                        ])));
                    },
                ],
                'endpoint' => 'https://custom-s3.example.com',
            ]);

            $this->assertSame('container-key', $config['credentials']()->wait()->getAccessKeyId());
            $this->assertSame('container-token', $config['credentials']()->wait()->getSecurityToken());
            $this->assertSame(1, $requests);
            $this->assertSame('https://custom-s3.example.com', $config['endpoint']);
        } finally {
            putenv($previous === false ? 'AWS_CONTAINER_CREDENTIALS_RELATIVE_URI' : 'AWS_CONTAINER_CREDENTIALS_RELATIVE_URI='.$previous);
        }
    }

    public function testS3InstanceCredentialProvider()
    {
        $config = $this->s3Config(['credentials' => 'instance']);

        $this->assertIsCallable($config['credentials']);
    }

    public function testS3RejectsUnknownCredentialProviders()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid credential provider [unknown].');

        $this->s3Config(['credentials' => 'unknown']);
    }

    public function testS3PreservesExistingCredentialConfigurations()
    {
        foreach ([false, ['key' => 'key', 'secret' => 'secret'], fn () => null] as $credentials) {
            $this->assertSame($credentials, $this->s3Config(['credentials' => $credentials])['credentials']);
        }

        $this->assertArrayNotHasKey('credentials', $this->s3Config([]));
        $this->assertSame(['key' => 'key', 'secret' => 'secret', 'token' => 'token'], $this->s3Config([
            'key' => 'key', 'secret' => 'secret', 'token' => 'token',
        ])['credentials']);
    }

    protected function s3Config(array $config): array
    {
        return (new class(new Application) extends FilesystemManager
        {
            public function config(array $config): array
            {
                return $this->formatS3Config($config);
            }
        })->config($config);
    }

    public function testExceptionThrownOnUnsupportedDriver()
    {
        $this->expectExceptionObject(new InvalidArgumentException('Disk [local] does not have a configured driver.'));

        $filesystem = new FilesystemManager(tap(new Application, function ($app) {
            $app['config'] = ['filesystems.disks.local' => null];
        }));

        $filesystem->disk('local');
    }

    public function testCanBuildOnDemandDisk()
    {
        $filesystem = new FilesystemManager(new Application);

        $this->assertInstanceOf(FilesystemContract::class, $filesystem->build('my-custom-path'));

        $this->assertInstanceOf(FilesystemContract::class, $filesystem->build([
            'driver' => 'local',
            'root' => 'my-custom-path',
            'url' => 'my-custom-url',
            'visibility' => 'public',
        ]));

        rmdir(__DIR__.'/../../my-custom-path');
    }

    public function testCanBuildReadOnlyDisks()
    {
        $filesystem = new FilesystemManager(new Application);

        $disk = $filesystem->build([
            'driver' => 'local',
            'read-only' => true,
            'root' => 'my-custom-path',
            'url' => 'my-custom-url',
            'visibility' => 'public',
        ]);

        file_put_contents(__DIR__.'/../../my-custom-path/path.txt', 'contents');

        // read operations work
        $this->assertSame('contents', $disk->get('path.txt'));
        $this->assertEquals(['path.txt'], $disk->files());

        // write operations fail
        $this->assertFalse($disk->put('path.txt', 'contents'));
        $this->assertFalse($disk->delete('path.txt'));
        $this->assertFalse($disk->deleteDirectory('directory'));
        $this->assertFalse($disk->prepend('path.txt', 'data'));
        $this->assertFalse($disk->append('path.txt', 'data'));
        $handle = fopen('php://memory', 'rw');
        fwrite($handle, 'content');
        $this->assertFalse($disk->writeStream('path.txt', $handle));
        fclose($handle);

        unlink(__DIR__.'/../../my-custom-path/path.txt');
        rmdir(__DIR__.'/../../my-custom-path');
    }

    public function testCanBuildScopedDisks()
    {
        try {
            $filesystem = new FilesystemManager(tap(new Application, function ($app) {
                $app['config'] = [
                    'filesystems.disks.local' => [
                        'driver' => 'local',
                        'root' => 'to-be-scoped',
                    ],
                ];
            }));

            $local = $filesystem->disk('local');
            $scoped = $filesystem->build([
                'driver' => 'scoped',
                'disk' => 'local',
                'prefix' => 'path-prefix',
            ]);

            $scoped->put('dirname/filename.txt', 'file content');
            $this->assertSame('file content', $local->get('path-prefix/dirname/filename.txt'));
            $local->deleteDirectory('path-prefix');
        } finally {
            rmdir(__DIR__.'/../../to-be-scoped');
        }
    }

    public function testCanBuildScopedDiskFromScopedDisk()
    {
        try {
            $filesystem = new FilesystemManager(tap(new Application, function ($app) {
                $app['config'] = [
                    'filesystems.disks.local' => [
                        'driver' => 'local',
                        'root' => 'root-to-be-scoped',
                    ],
                    'filesystems.disks.scoped-from-root' => [
                        'driver' => 'scoped',
                        'disk' => 'local',
                        'prefix' => 'scoped-from-root-prefix',
                    ],
                ];
            }));

            $root = $filesystem->disk('local');
            $nestedScoped = $filesystem->build([
                'driver' => 'scoped',
                'disk' => 'scoped-from-root',
                'prefix' => 'nested-scoped-prefix',
            ]);

            $nestedScoped->put('dirname/filename.txt', 'file content');
            $this->assertSame('file content', $root->get('scoped-from-root-prefix/nested-scoped-prefix/dirname/filename.txt'));
            $root->deleteDirectory('scoped-from-root-prefix');
        } finally {
            rmdir(__DIR__.'/../../root-to-be-scoped');
        }
    }

    #[RequiresOperatingSystem('Linux|Darwin')]
    public function testCanBuildScopedDisksWithVisibility()
    {
        try {
            $filesystem = new FilesystemManager(tap(new Application, function ($app) {
                $app['config'] = [
                    'filesystems.disks.local' => [
                        'driver' => 'local',
                        'root' => 'to-be-scoped',
                        'visibility' => 'public',
                    ],
                ];
            }));

            $scoped = $filesystem->build([
                'driver' => 'scoped',
                'disk' => 'local',
                'prefix' => 'path-prefix',
                'visibility' => 'private',
            ]);

            $scoped->put('dirname/filename.txt', 'file content');

            $this->assertSame('private', $scoped->getVisibility('dirname/filename.txt'));
        } finally {
            unlink(__DIR__.'/../../to-be-scoped/path-prefix/dirname/filename.txt');
            rmdir(__DIR__.'/../../to-be-scoped/path-prefix/dirname');
            rmdir(__DIR__.'/../../to-be-scoped/path-prefix');
            rmdir(__DIR__.'/../../to-be-scoped');
        }
    }

    public function testCanBuildScopedDisksWithThrow()
    {
        try {
            $filesystem = new FilesystemManager(tap(new Application, function ($app) {
                $app['config'] = [
                    'filesystems.disks.local' => [
                        'driver' => 'local',
                        'root' => 'to-be-scoped',
                        'throw' => false,
                    ],
                ];
            }));

            $scoped = $filesystem->build([
                'driver' => 'scoped',
                'disk' => 'local',
                'prefix' => 'path-prefix',
                'throw' => true,
            ]);

            $this->expectException(UnableToReadFile::class);
            $scoped->get('dirname/filename.txt');
        } finally {
            rmdir(__DIR__.'/../../to-be-scoped');
        }
    }

    public function testCanBuildInlineScopedDisks()
    {
        try {
            $filesystem = new FilesystemManager(new Application);

            $scoped = $filesystem->build([
                'driver' => 'scoped',
                'disk' => [
                    'driver' => 'local',
                    'root' => 'to-be-scoped',
                ],
                'prefix' => 'path-prefix',
            ]);

            $scoped->put('dirname/filename.txt', 'file content');
            $this->assertDirectoryExists(__DIR__.'/../../to-be-scoped/path-prefix');
            $this->assertSame('file content', file_get_contents(__DIR__.'/../../to-be-scoped/path-prefix/dirname/filename.txt'));
        } finally {
            unlink(__DIR__.'/../../to-be-scoped/path-prefix/dirname/filename.txt');
            rmdir(__DIR__.'/../../to-be-scoped/path-prefix/dirname');
            rmdir(__DIR__.'/../../to-be-scoped/path-prefix');
            rmdir(__DIR__.'/../../to-be-scoped');
        }
    }

    public function testCanBuildReadThroughDisks()
    {
        $filesystem = $this->readThroughFilesystemManager();
        $primary = $filesystem->disk('primary');
        $fallback = $filesystem->disk('fallback');
        $readThrough = $filesystem->disk('read-through');

        $fallback->put('fallback.txt', 'fallback contents');
        $fallback->put('hidden-from-listing.txt', 'contents');
        $primary->put('primary.txt', 'primary contents');
        $primary->put('preferred.txt', 'primary version');
        $fallback->put('preferred.txt', 'fallback version');

        $this->assertTrue($readThrough->exists('fallback.txt'));
        $this->assertSame(strlen('fallback contents'), $readThrough->size('fallback.txt'));
        $this->assertTrue($primary->missing('fallback.txt'));
        $this->assertSame(['preferred.txt', 'primary.txt'], $readThrough->files());

        $this->assertSame('fallback contents', $readThrough->get('fallback.txt'));
        $this->assertSame('fallback contents', $primary->get('fallback.txt'));
        $this->assertSame('primary version', $readThrough->get('preferred.txt'));

        $readThrough->put('written.txt', 'written contents');

        $this->assertSame('written contents', $primary->get('written.txt'));
        $this->assertTrue($fallback->missing('written.txt'));
    }

    public function testReadThroughDisksPromoteStreams()
    {
        $filesystem = $this->readThroughFilesystemManager();
        $primary = $filesystem->disk('primary');
        $fallback = $filesystem->disk('fallback');
        $readThrough = $filesystem->disk('read-through');

        $fallback->put('stream.txt', 'stream contents');

        $stream = $readThrough->readStream('stream.txt');

        $this->assertSame('stream contents', stream_get_contents($stream));
        $this->assertSame('stream contents', $primary->get('stream.txt'));

        fclose($stream);
    }

    public function testReadThroughDisksDoNotCopyWhenDisabled()
    {
        $filesystem = $this->readThroughFilesystemManager([
            'copy' => false,
        ]);
        $primary = $filesystem->disk('primary');
        $fallback = $filesystem->disk('fallback');
        $readThrough = $filesystem->disk('read-through');

        $fallback->put('fallback.txt', 'fallback contents');

        $this->assertSame('fallback contents', $readThrough->get('fallback.txt'));
        $this->assertTrue($primary->missing('fallback.txt'));

        $fallback->put('stream.txt', 'stream contents');

        $stream = $readThrough->readStream('stream.txt');

        $this->assertSame('stream contents', stream_get_contents($stream));
        $this->assertTrue($primary->missing('stream.txt'));

        fclose($stream);
    }

    public function testReadThroughDisksDeleteFilesFromBothDisks()
    {
        $filesystem = $this->readThroughFilesystemManager();
        $primary = $filesystem->disk('primary');
        $fallback = $filesystem->disk('fallback');
        $readThrough = $filesystem->disk('read-through');

        $fallback->put('file.txt', 'contents');
        $readThrough->get('file.txt');

        $this->assertTrue($readThrough->delete('file.txt'));
        $this->assertTrue($primary->missing('file.txt'));
        $this->assertTrue($fallback->missing('file.txt'));
        $this->assertTrue($readThrough->missing('file.txt'));
    }

    public function testReadThroughDisksDeleteDirectoriesFromBothDisks()
    {
        $filesystem = $this->readThroughFilesystemManager();
        $primary = $filesystem->disk('primary');
        $fallback = $filesystem->disk('fallback');
        $readThrough = $filesystem->disk('read-through');

        $primary->put('directory/primary.txt', 'primary contents');
        $fallback->put('directory/fallback.txt', 'fallback contents');

        $this->assertTrue($readThrough->deleteDirectory('directory'));
        $this->assertTrue($primary->directoryMissing('directory'));
        $this->assertTrue($fallback->directoryMissing('directory'));
        $this->assertTrue($readThrough->directoryMissing('directory'));
    }

    public function testReadThroughDisksDoNotResurrectMovedFiles()
    {
        $filesystem = $this->readThroughFilesystemManager();
        $primary = $filesystem->disk('primary');
        $fallback = $filesystem->disk('fallback');
        $readThrough = $filesystem->disk('read-through');

        $fallback->put('source.txt', 'contents');
        $readThrough->get('source.txt');

        $this->assertTrue($readThrough->move('source.txt', 'destination.txt'));
        $this->assertSame('contents', $primary->get('destination.txt'));
        $this->assertTrue($primary->missing('source.txt'));
        $this->assertTrue($fallback->missing('source.txt'));
        $this->assertTrue($readThrough->missing('source.txt'));
    }

    public function testReadThroughDisksMoveFilesThatOnlyExistOnTheFallbackDisk()
    {
        $filesystem = $this->readThroughFilesystemManager();
        $primary = $filesystem->disk('primary');
        $fallback = $filesystem->disk('fallback');
        $readThrough = $filesystem->disk('read-through');

        $fallback->put('source.txt', 'contents');

        $this->assertTrue($readThrough->move('source.txt', 'destination.txt'));
        $this->assertSame('contents', $primary->get('destination.txt'));
        $this->assertTrue($fallback->missing('source.txt'));
        $this->assertTrue($readThrough->missing('source.txt'));
    }

    public function testReadThroughDisksCopyFilesThatOnlyExistOnTheFallbackDisk()
    {
        $filesystem = $this->readThroughFilesystemManager();
        $primary = $filesystem->disk('primary');
        $fallback = $filesystem->disk('fallback');
        $readThrough = $filesystem->disk('read-through');

        $fallback->put('source.txt', 'contents');

        $this->assertTrue($readThrough->copy('source.txt', 'destination.txt'));
        $this->assertSame('contents', $primary->get('destination.txt'));
        $this->assertSame('contents', $fallback->get('source.txt'));
        $this->assertSame('contents', $readThrough->get('source.txt'));
    }

    public function testReadThroughDisksCopyFromTheFallbackDiskWithoutPromotingTheSourceWhenDisabled()
    {
        $filesystem = $this->readThroughFilesystemManager([
            'copy' => false,
        ]);
        $primary = $filesystem->disk('primary');
        $fallback = $filesystem->disk('fallback');
        $readThrough = $filesystem->disk('read-through');

        $fallback->put('source.txt', 'contents');

        $this->assertTrue($readThrough->copy('source.txt', 'destination.txt'));
        $this->assertSame('contents', $primary->get('destination.txt'));
        $this->assertTrue($primary->missing('source.txt'));
        $this->assertSame('contents', $fallback->get('source.txt'));

        $this->assertTrue($readThrough->move('source.txt', 'moved.txt'));
        $this->assertSame('contents', $primary->get('moved.txt'));
        $this->assertTrue($primary->missing('source.txt'));
        $this->assertTrue($fallback->missing('source.txt'));
    }

    public function testReadThroughDisksFailToMoveOrCopyMissingFiles()
    {
        $filesystem = $this->readThroughFilesystemManager();
        $readThrough = $filesystem->disk('read-through');

        $this->assertFalse($readThrough->move('missing.txt', 'destination.txt'));
        $this->assertFalse($readThrough->copy('missing.txt', 'destination.txt'));
    }

    public function testReadThroughDisksFailToMoveOrCopyWhenThePrimaryDiskIsUnwritable()
    {
        $filesystem = $this->readThroughFilesystemManager([
            'primary' => [
                'driver' => 'local',
                'root' => $this->temporaryDirectory('primary'),
                'read-only' => true,
            ],
        ]);
        $fallback = $filesystem->disk('fallback');
        $readThrough = $filesystem->disk('read-through');

        $fallback->put('source.txt', 'contents');

        $this->assertFalse($readThrough->move('source.txt', 'destination.txt'));
        $this->assertFalse($readThrough->copy('source.txt', 'destination.txt'));
        $this->assertSame('contents', $fallback->get('source.txt'));
    }

    public function testReadThroughDiskPromotionFailuresAreBestEffortByDefault()
    {
        $filesystem = $this->readThroughFilesystemManager([
            'primary' => [
                'driver' => 'local',
                'root' => $this->temporaryDirectory('primary'),
                'read-only' => true,
            ],
        ]);

        $filesystem->disk('fallback')->put('fallback.txt', 'fallback contents');

        $this->assertSame('fallback contents', $filesystem->disk('read-through')->get('fallback.txt'));
    }

    public function testReadThroughDiskCanThrowOnPromotionFailures()
    {
        $filesystem = $this->readThroughFilesystemManager([
            'primary' => [
                'driver' => 'local',
                'root' => $this->temporaryDirectory('primary'),
                'read-only' => true,
            ],
            'throw' => true,
            'throw_on_promotion_failure' => true,
        ]);

        $filesystem->disk('fallback')->put('fallback.txt', 'fallback contents');

        $this->expectException(UnableToReadFile::class);

        $filesystem->disk('read-through')->get('fallback.txt');
    }

    public function testReadThroughDisksDelegateUrlsToTheDiskContainingTheFile()
    {
        $filesystem = $this->readThroughFilesystemManager([], [
            'url' => 'https://primary.test',
        ], [
            'url' => 'https://fallback.test',
        ]);
        $primary = $filesystem->disk('primary');
        $fallback = $filesystem->disk('fallback');
        $readThrough = $filesystem->disk('read-through');
        $expiration = Carbon::create(2026, 8, 11);

        $primary->put('primary.txt', 'primary contents');
        $fallback->put('fallback.txt', 'fallback contents');
        $primary->buildTemporaryUrlsUsing(fn ($path, $expiration, $options) => 'primary/'.$path.'/'.$options['version']);
        $fallback->buildTemporaryUrlsUsing(fn ($path, $expiration, $options) => 'fallback/'.$path.'/'.$options['version']);
        $primary->buildTemporaryUploadUrlsUsing(fn ($path, $expiration, $options) => [
            'url' => 'upload/'.$path.'/'.$options['version'],
            'headers' => ['X-Test' => 'header'],
        ]);

        $this->assertSame('https://primary.test/primary.txt', $readThrough->url('primary.txt'));
        $this->assertSame('https://fallback.test/fallback.txt', $readThrough->url('fallback.txt'));
        $this->assertTrue($readThrough->providesTemporaryUrls());
        $this->assertSame('primary/primary.txt/1', $readThrough->temporaryUrl('primary.txt', $expiration, ['version' => 1]));
        $this->assertSame('fallback/fallback.txt/1', $readThrough->temporaryUrl('fallback.txt', $expiration, ['version' => 1]));
        $this->assertTrue($readThrough->providesTemporaryUploadUrls());
        $this->assertSame([
            'url' => 'upload/file.txt/1',
            'headers' => ['X-Test' => 'header'],
        ], $readThrough->temporaryUploadUrl('file.txt', $expiration, ['version' => 1]));
    }

    public function testCustomDriverClosureBoundObjectIsFilesystemManager()
    {
        $manager = new FilesystemManager(tap(new Application, function ($app) {
            $app['config'] = [
                'filesystems.disks.'.__CLASS__ => [
                    'driver' => __CLASS__,
                ],
            ];
        }));
        $manager->extend(__CLASS__, fn () => $this);
        $this->assertSame($manager, $manager->disk(__CLASS__));
    }

    public function testCustomDriverStaticClosure()
    {
        $manager = new FilesystemManager(tap(new Application, static function ($app) {
            $app['config'] = [
                'filesystems.disks.'.__CLASS__ => [
                    'driver' => __CLASS__,
                ],
            ];
        }));

        $driver = new stdClass;

        $manager->extend(__CLASS__, static fn () => $driver);
        $this->assertSame($driver, $manager->disk(__CLASS__));
    }

    public function testInvokableObjectDriverClosure()
    {
        $manager = new FilesystemManager(tap(new Application, static function ($app) {
            $app['config'] = [
                'filesystems.disks.'.__CLASS__ => [
                    'driver' => __CLASS__,
                ],
            ];
        }));

        $driver = new stdClass;
        $creator = new CustomFilesystemDriver($driver);

        $manager->extend(__CLASS__, $creator(...));
        $this->assertSame($driver, $manager->disk(__CLASS__));
    }

    // public function testKeepTrackOfAdapterDecoration()
    // {
    //     try {
    //         $filesystem = new FilesystemManager(tap(new Application, function ($app) {
    //             $app['config'] = [
    //                 'filesystems.disks.local' => [
    //                     'driver' => 'local',
    //                     'root' => 'to-be-scoped',
    //                 ],
    //             ];
    //         }));

    //         $scoped = $filesystem->build([
    //             'driver' => 'scoped',
    //             'disk' => 'local',
    //             'prefix' => 'path-prefix',
    //         ]);

    //         $this->assertInstanceOf(PathPrefixedAdapter::class, $scoped->getAdapter());
    //     } finally {
    //         rmdir(__DIR__.'/../../to-be-scoped');
    //     }
    // }

    protected function readThroughFilesystemManager(
        array $readThroughConfig = [],
        array $primaryConfig = [],
        array $fallbackConfig = [],
    ): FilesystemManager {
        $primary = $this->temporaryDirectory('primary');
        $fallback = $this->temporaryDirectory('fallback');

        return new FilesystemManager(tap(new Application, function ($app) use ($primary, $fallback, $readThroughConfig, $primaryConfig, $fallbackConfig) {
            $app['config'] = [
                'filesystems.disks.primary' => array_replace([
                    'driver' => 'local',
                    'root' => $primary,
                ], $primaryConfig),
                'filesystems.disks.fallback' => array_replace([
                    'driver' => 'local',
                    'root' => $fallback,
                ], $fallbackConfig),
                'filesystems.disks.read-through' => array_replace([
                    'driver' => 'read-through',
                    'primary' => 'primary',
                    'fallback' => 'fallback',
                ], $readThroughConfig),
            ];
        }));
    }

    protected function temporaryDirectory(string $name): string
    {
        return $this->temporaryDirectories[] = sys_get_temp_dir().'/laravel-read-through-'.$name.'-'.uniqid();
    }
}

class CustomFilesystemDriver
{
    public function __construct(private object $driver)
    {
    }

    public function __invoke()
    {
        return $this->driver;
    }
}
