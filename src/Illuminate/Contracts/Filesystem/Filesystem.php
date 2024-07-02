<?php

namespace Illuminate\Contracts\Filesystem;

use Closure;

interface Filesystem
{
    /**
     * The public visibility setting.
     *
     * @var string
     */
    const VISIBILITY_PUBLIC = 'public';

    /**
     * The private visibility setting.
     *
     * @var string
     */
    const VISIBILITY_PRIVATE = 'private';

    /**
     * Assert that the given file or directory exists.
     *
     * @param  string|array  $path
     * @param  string|null  $content
     * @return $this
     */
    public function assertExists($path, $content = null);

    /**
     * Assert that the given file or directory does not exist.
     *
     * @param  string|array  $path
     * @return $this
     */
    public function assertMissing($path);

    /**
     * Assert that the given directory is empty.
     *
     * @param  string  $path
     * @return $this
     */
    public function assertDirectoryEmpty($path);

    /**
     * Get the full path to the file that exists at the given relative path.
     *
     * @param  string  $path
     * @return string
     */
    public function path($path);

    /**
     * Determine if a file exists.
     *
     * @param  string  $path
     * @return bool
     */
    public function exists($path);

    /**
     * Determine if a file or directory is missing.
     *
     * @param  string  $path
     * @return bool
     */
    public function missing($path);

    /**
     * Determine if a file exists.
     *
     * @param  string  $path
     * @return bool
     */
    public function fileExists($path);

    /**
     * Determine if a file is missing.
     *
     * @param  string  $path
     * @return bool
     */
    public function fileMissing($path);

    /**
     * Determine if a directory exists.
     *
     * @param  string  $path
     * @return bool
     */
    public function directoryExists($path);

    /**
     * Determine if a directory is missing.
     *
     * @param  string  $path
     * @return bool
     */
    public function directoryMissing($path);

    /**
     * Get the contents of a file.
     *
     * @param  string  $path
     * @return string|null
     */
    public function get($path);

    /**
     * Get a resource to read the file.
     *
     * @param  string  $path
     * @return resource|null The path resource or null on failure.
     */
    public function readStream($path);

    /**
     * Get the contents of a file as decoded JSON.
     *
     * @param  string  $path
     * @param  int  $flags
     * @return array|null
     */
    public function json($path, $flags = 0);

    /**
     * Create a streamed response for a given file.
     *
     * @param  string  $path
     * @param  string|null  $name
     * @param  array  $headers
     * @param  string|null  $disposition
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function response($path, $name = null, array $headers = [], $disposition = 'inline');

    /**
     * Create a streamed download response for a given file.
     *
     * @param  string  $path
     * @param  string|null  $name
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function download($path, $name = null, array $headers = []);

    /**
     * Write the contents of a file.
     *
     * @param  string  $path
     * @param  \Psr\Http\Message\StreamInterface|\Illuminate\Http\File|\Illuminate\Http\UploadedFile|string|resource  $contents
     * @param  mixed  $options
     * @return string|bool
     */
    public function put($path, $contents, $options = []);

    /**
     * Store the uploaded file on the disk.
     *
     * @param  \Illuminate\Http\File|\Illuminate\Http\UploadedFile|string  $path
     * @param  \Illuminate\Http\File|\Illuminate\Http\UploadedFile|string|array|null  $file
     * @param  mixed  $options
     * @return string|false
     */
    public function putFile($path, $file = null, $options = []);

    /**
     * Store the uploaded file on the disk with a given name.
     *
     * @param  \Illuminate\Http\File|\Illuminate\Http\UploadedFile|string  $path
     * @param  \Illuminate\Http\File|\Illuminate\Http\UploadedFile|string|array|null  $file
     * @param  string|array|null  $name
     * @param  mixed  $options
     * @return string|false
     */
    public function putFileAs($path, $file, $name = null, $options = []);

    /**
     * Write a new file using a stream.
     *
     * @param  string  $path
     * @param  resource  $resource
     * @param  array  $options
     * @return bool
     */
    public function writeStream($path, $resource, array $options = []);

    /**
     * Get the visibility for the given path.
     *
     * @param  string  $path
     * @return string
     */
    public function getVisibility($path);

    /**
     * Set the visibility for the given path.
     *
     * @param  string  $path
     * @param  string  $visibility
     * @return bool
     */
    public function setVisibility($path, $visibility);

    /**
     * Prepend to a file.
     *
     * @param  string  $path
     * @param  string  $data
     * @param  string  $separator
     * @return bool
     */
    public function prepend($path, $data, $separator = PHP_EOL);

    /**
     * Append to a file.
     *
     * @param  string  $path
     * @param  string  $data
     * @param  string  $separator
     * @return bool
     */
    public function append($path, $data, $separator = PHP_EOL);

    /**
     * Delete the file at a given path.
     *
     * @param  string|array  $paths
     * @return bool
     */
    public function delete($paths);

    /**
     * Copy a file to a new location.
     *
     * @param  string  $from
     * @param  string  $to
     * @return bool
     */
    public function copy($from, $to);

    /**
     * Move a file to a new location.
     *
     * @param  string  $from
     * @param  string  $to
     * @return bool
     */
    public function move($from, $to);

    /**
     * Get the file size of a given file.
     *
     * @param  string  $path
     * @return int
     */
    public function size($path);

    /**
     * Get the checksum for a file.
     *
     * @return string|false
     *
     * @throws \League\Flysystem\UnableToProvideChecksum
     */
    public function checksum(string $path, array $options = []);

    /**
     * Get the mime-type of a given file.
     *
     * @param  string  $path
     * @return string|false
     */
    public function mimeType($path);

    /**
     * Get the file's last modification time.
     *
     * @param  string  $path
     * @return int
     */
    public function lastModified($path);

    /**
     * Determine if temporary URLs can be generated.
     *
     * @return bool
     */
    public function providesTemporaryUrls();

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
    public function temporaryUrl($path, $expiration, array $options = []);

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
    public function temporaryUploadUrl($path, $expiration, array $options = []);

    /**
     * Get an array of all files in a directory.
     *
     * @param  string|null  $directory
     * @param  bool  $recursive
     * @return array
     */
    public function files($directory = null, $recursive = false);

    /**
     * Get all of the files from the given directory (recursive).
     *
     * @param  string|null  $directory
     * @return array
     */
    public function allFiles($directory = null);

    /**
     * Get all of the directories within a given directory.
     *
     * @param  string|null  $directory
     * @param  bool  $recursive
     * @return array
     */
    public function directories($directory = null, $recursive = false);

    /**
     * Get all the directories within a given directory (recursive).
     *
     * @param  string|null  $directory
     * @return array
     */
    public function allDirectories($directory = null);

    /**
     * Create a directory.
     *
     * @param  string  $path
     * @return bool
     */
    public function makeDirectory($path);

    /**
     * Recursively delete a directory.
     *
     * @param  string  $directory
     * @return bool
     */
    public function deleteDirectory($directory);

    /**
     * Get the Flysystem driver.
     *
     * @return \League\Flysystem\FilesystemOperator
     */
    public function getDriver();

    /**
     * Get the Flysystem adapter.
     *
     * @return \League\Flysystem\FilesystemAdapter
     */
    public function getAdapter();

    /**
     * Get the configuration values.
     *
     * @return array
     */
    public function getConfig();

    /**
     * Define a custom temporary URL builder callback.
     *
     * @param  \Closure  $callback
     * @return void
     */
    public function buildTemporaryUrlsUsing(Closure $callback);
}
