<?php

namespace Illuminate\Filesystem;

use Aws\S3\S3Client;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Traits\Conditionable;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter as S3Adapter;
use League\Flysystem\FilesystemOperator;
use Psr\Http\Message\UriInterface;
use RuntimeException;

class AwsS3V3Adapter extends FilesystemAdapter
{
    use Conditionable;

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
     *
     * @throws \RuntimeException If the bucket configuration is missing
     */
    public function __construct(FilesystemOperator $driver, S3Adapter $adapter, array $config, S3Client $client)
    {
        foreach (['bucket', 'region', 'credentials'] as $key) {
            if (empty($config[$key])) {
                throw new RuntimeException("S3 configuration key '{$key}' is missing.");
            }
        }

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
        if (empty($path)) {
            throw new RuntimeException('Path cannot be empty.');
        }

        $prefixedPath = $this->prefixer->prefixPath($path);

        // If an explicit base URL has been set on the disk configuration then we will use
        // it as the base URL instead of the default path. This allows the developer to
        // have full control over the base path for this filesystem's generated URLs.
        if (isset($this->config['url'])) {
            return $this->concatPathToUrl($this->config['url'], $prefixedPath);
        }

        return $this->client->getObjectUrl(
            $this->config['bucket'], $prefixedPath
        );
    }

    /**
     * Determine if temporary URLs can be generated.
     *
     * @return bool
     */
    public function providesTemporaryUrls()
    {
        return true;
    }

    /**
     * Get a temporary URL for the file at the given path.
     *
     * @param  string  $path
     * @param  \DateTimeInterface  $expiration
     * @param  array  $options
     * @return string
     *
     * @throws \RuntimeException
     */
    public function temporaryUrl($path, $expiration, array $options = [])
    {
        if (empty($path)) {
            throw new RuntimeException('Path cannot be empty.');
        }

        $command = $this->client->getCommand('GetObject', array_merge([
            'Bucket' => $this->config['bucket'],
            'Key' => $this->prefixer->prefixPath($path),
        ], $options));

        $uri = $this->client->createPresignedRequest(
            $command, $expiration, $options
        )->getUri();

        return $this->applyTemporaryUrlOverride($uri);
    }

    /**
     * Get a temporary upload URL for the file at the given path.
     *
     * @param  string  $path
     * @param  \DateTimeInterface  $expiration
     * @param  array  $options
     * @return array
     *
     * @throws \RuntimeException
     */
    public function temporaryUploadUrl($path, $expiration, array $options = [])
    {
        if (empty($path)) {
            throw new RuntimeException('Path cannot be empty.');
        }

        $command = $this->client->getCommand('PutObject', array_merge([
            'Bucket' => $this->config['bucket'],
            'Key' => $this->prefixer->prefixPath($path),
        ], $options));

        $signedRequest = $this->client->createPresignedRequest(
            $command, $expiration, $options
        );

        $uri = $this->applyTemporaryUrlOverride($signedRequest->getUri());

        return [
            'url' => (string) $uri,
            'headers' => $signedRequest->getHeaders(),
        ];
    }

    /**
     * Apply temporary URL override if configured.
     *
     * @param  \Psr\Http\Message\UriInterface  $uri
     * @return \Psr\Http\Message\UriInterface
     */
    protected function applyTemporaryUrlOverride(UriInterface $uri)
    {
        if (isset($this->config['temporary_url'])) {
            $uri = $this->replaceBaseUrl($uri, $this->config['temporary_url']);
        }

        return $uri;
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

    /**
     * Generate a pre-signed POST policy for uploads.
     *
     * @param  string  $path
     * @param  array  $options
     * @param  \DateTimeInterface|null  $expiration
     * @return array
     *
     * @throws \RuntimeException
     */
    public function createPresignedPostPolicy($path, array $options = [], ?DateTimeInterface $expiration = null)
    {
        if (empty($path)) {
            throw new RuntimeException('Path cannot be empty.');
        }

        $expiration = $expiration ?: now()->addHour();

        $formInputs = [
            'key' => $this->prefixer->prefixPath($path),
        ];

        $postObject = new \Aws\S3\PostObjectV4(
            $this->client,
            $this->config['bucket'],
            $formInputs,
            $options,
            $expiration
        );

        return [
            'url' => $this->applyTemporaryUrlOverride($postObject->getFormAttributes()['action']),
            'fields' => $postObject->getFormInputs(),
        ];
    }

    /**
     * Get the S3 bucket name.
     *
     * @return string
     */
    public function getBucket()
    {
        return $this->config['bucket'];
    }

    /**
     * Get the S3 region.
     *
     * @return string|null
     */
    public function getRegion()
    {
        return $this->config['region'] ?? null;
    }

    /**
     * Check if a file exists in the bucket.
     *
     * @param  string  $path
     * @return bool
     */
    public function exists($path)
    {
        return $this->driver->fileExists($this->prefixer->prefixPath($path));
    }

    /**
     * Get the file size in bytes.
     *
     * @param  string  $path
     * @return int
     *
     * @throws \League\Flysystem\FilesystemException
     */
    public function size($path)
    {
        return $this->driver->fileSize($this->prefixer->prefixPath($path));
    }

    /**
     * Get the file's last modification time.
     *
     * @param  string  $path
     * @return int
     *
     * @throws \League\Flysystem\FilesystemException
     */
    public function lastModified($path)
    {
        return $this->driver->lastModified($this->prefixer->prefixPath($path));
    }

    /**
     * Get the file's visibility.
     *
     * @param  string  $path
     * @return string
     *
     * @throws \League\Flysystem\FilesystemException
     */
    public function visibility($path)
    {
        return $this->driver->visibility($this->prefixer->prefixPath($path));
    }

    /**
     * Set the file's visibility.
     *
     * @param  string  $path
     * @param  string  $visibility
     * @return bool
     *
     * @throws \League\Flysystem\FilesystemException
     */
    public function setVisibility($path, $visibility)
    {
        return $this->driver->setVisibility($this->prefixer->prefixPath($path), $visibility);
    }

    /**
     * Get the fully-prefixed path for the given file.
     *
     * This method applies the internal prefixer to the specified path,
     * ensuring that it includes any configured root or directory prefix.
     *
     * @param  string  $path
     * @return string
     *
     * @throws \RuntimeException If the prefixer property is not available.
     */
    protected function getPrefixedPath($path)
    {
        if (! property_exists($this, 'prefixer')) {
            throw new RuntimeException('Prefixer is not available in this adapter.');
        }

        return $this->prefixer->prefixPath($path);
    }

}
