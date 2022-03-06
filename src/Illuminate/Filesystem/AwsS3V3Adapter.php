<?php

namespace Illuminate\Filesystem;

use Aws\S3\S3Client;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter as S3Adapter;
use League\Flysystem\FilesystemOperator;

class AwsS3V3Adapter extends FilesystemAdapter
{
    /**
     * The AWS S3 client.
     *
     * @var \Aws\S3\S3Client
     */
    protected $client;

    /**
     * Create a new AwsS3V3FilesystemAdapter instance.
     *
     * @param  \League\Flysystem\FilesystemOperator  $driver
     * @param  \League\Flysystem\AwsS3V3\AwsS3V3Adapter  $adapter
     * @param  array  $config
     * @param  \Aws\S3\S3Client  $client
     * @return void
     */
    public function __construct(FilesystemOperator $driver, S3Adapter $adapter, array $config, S3Client $client)
    {
        parent::__construct($driver, $adapter, $config);

        $this->client = $client;
    }

    /**
     * Get the URL for the file at the given path.
     *
     * @param  string  $path
     * @return string
     *
     * @throws \RuntimeException
     */
    public function url($path)
    {
        // If an explicit base URL has been set on the disk configuration then we will use
        // it as the base URL instead of the default path. This allows the developer to
        // have full control over the base path for this filesystem's generated URLs.
        if (isset($this->config['url'])) {
            return $this->concatPathToUrl($this->config['url'], $this->prefixer->prefixPath($path));
        }

        return $this->client->getObjectUrl(
            $this->config['bucket'], $this->prefixer->prefixPath($path)
        );
    }

    /**
     * Get a temporary URL for the file at the given path.
     *
     * @param  string  $path
     * @param  \DateTimeInterface  $expiration
     * @param  array  $options
     * @return string
     */
    public function temporaryUrl($path, $expiration, array $options = [])
    {
        $command = $this->client->getCommand('GetObject', array_merge([
            'Bucket' => $this->config['bucket'],
            'Key' => $this->prefixer->prefixPath($path),
        ], $options));

        $uri = $this->client->createPresignedRequest(
            $command, $expiration, $options
        )->getUri();

        // If an explicit base URL has been set on the disk configuration then we will use
        // it as the base URL instead of the default path. This allows the developer to
        // have full control over the base path for this filesystem's generated URLs.
        if (isset($this->config['temporary_url'])) {
            $uri = $this->replaceBaseUrl($uri, $this->config['temporary_url']);
        }

        return (string) $uri;
    }

    /**
     * Get the underlying S3 client.
     *
     * @return \Aws\S3\S3Client
     */
    public function getClient()
    {
        return $this->client;
    }
}
