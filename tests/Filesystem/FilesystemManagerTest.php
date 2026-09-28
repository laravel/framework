<?php

namespace Illuminate\Tests\Filesystem;

use Aws\Exception\CredentialsException;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Foundation\Application;
use InvalidArgumentException;
use League\Flysystem\PathPrefixing\PathPrefixedAdapter;
use League\Flysystem\UnableToReadFile;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;
use PHPUnit\Framework\TestCase;

class FilesystemManagerTest extends TestCase
{
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
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Disk [local] does not have a configured driver.');

        $filesystem = new FilesystemManager(tap(new Application, function ($app) {
            $app['config'] = ['filesystems.disks.local' => null];
        }));

        $filesystem->disk('local');
    }

    public function testCanBuildOnDemandDisk()
    {
        $filesystem = new FilesystemManager(new Application);

        $this->assertInstanceOf(Filesystem::class, $filesystem->build('my-custom-path'));

        $this->assertInstanceOf(Filesystem::class, $filesystem->build([
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
        $this->assertEquals('contents', $disk->get('path.txt'));
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
            $this->assertEquals('file content', $local->get('path-prefix/dirname/filename.txt'));
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
            $this->assertEquals('file content', $root->get('scoped-from-root-prefix/nested-scoped-prefix/dirname/filename.txt'));
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

            $this->assertEquals('private', $scoped->getVisibility('dirname/filename.txt'));
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
            $this->assertTrue(is_dir(__DIR__.'/../../to-be-scoped/path-prefix'));
            $this->assertEquals(file_get_contents(__DIR__.'/../../to-be-scoped/path-prefix/dirname/filename.txt'), 'file content');
        } finally {
            unlink(__DIR__.'/../../to-be-scoped/path-prefix/dirname/filename.txt');
            rmdir(__DIR__.'/../../to-be-scoped/path-prefix/dirname');
            rmdir(__DIR__.'/../../to-be-scoped/path-prefix');
            rmdir(__DIR__.'/../../to-be-scoped');
        }
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
}
