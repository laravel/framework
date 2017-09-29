<?php

namespace Illuminate\Support\Facades;

use Illuminate\Filesystem\Filesystem;

/**
 * @method static \Illuminate\Filesystem\FilesystemAdapter drive(string $name) Get a filesystem instance.
 * @method static \Illuminate\Filesystem\FilesystemAdapter disk(string $name) Get a filesystem instance.
 * @method static \Illuminate\Filesystem\FilesystemAdapter cloud() Get a default cloud filesystem instance.
 * @method static \Illuminate\Filesystem\FilesystemAdapter createLocalDriver(array $config) Create an instance of the local driver.
 * @method static \Illuminate\Filesystem\FilesystemAdapter createFtpDriver(array $config) Create an instance of the ftp driver.
 * @method static \Illuminate\Contracts\Filesystem\Cloud createS3Driver(array $config) Create an instance of the Amazon S3 driver.
 * @method static \Illuminate\Contracts\Filesystem\Cloud createRackspaceDriver(array $config) Create an instance of the Rackspace driver.
 * @method static void set(string $name, mixed $disk) Set the given disk instance.
 * @method static string getDefaultDriver() Get the default driver name.
 * @method static string getDefaultCloudDriver() Get the default cloud driver name.
 * @method static $this extend(string $driver, \Closure $callback) Register a custom driver creator Closure.
 * @method static void assertExists(string $path) Assert that the given file exists.
 * @method static void assertMissing(string $path) Assert that the given file does not exist.
 * @method static bool exists(string $path) Determine if a file exists.
 * @method static string path(string $path) Get the full path for the file at the given "short" path.
 * @method static string get(string $path) Get the contents of a file.
 * @method static bool put(string $path, string | resource $contents, mixed $options) Write the contents of a file.
 * @method static string|false putFile(string $path, \Illuminate\Http\File | \Illuminate\Http\UploadedFile $file, array $options) Store the uploaded file on the disk.
 * @method static string|false putFileAs(string $path, \Illuminate\Http\File | \Illuminate\Http\UploadedFile $file, string $name, array $options) Store the uploaded file on the disk with a given name.
 * @method static string getVisibility(string $path) Get the visibility for the given path.
 * @method static void setVisibility(string $path, string $visibility) Set the visibility for the given path.
 * @method static int prepend(string $path, string $data, string $separator) Prepend to a file.
 * @method static int append(string $path, string $data, string $separator) Append to a file.
 * @method static bool delete(string | array $paths) Delete the file at a given path.
 * @method static bool copy(string $from, string $to) Copy a file to a new location.
 * @method static bool move(string $from, string $to) Move a file to a new location.
 * @method static int size(string $path) Get the file size of a given file.
 * @method static string|false mimeType(string $path) Get the mime-type of a given file.
 * @method static int lastModified(string $path) Get the file's last modification time.
 * @method static string url(string $path) Get the URL for the file at the given path.
 * @method static string temporaryUrl(string $path, \DateTimeInterface $expiration, array $options) Get a temporary URL for the file at the given path.
 * @method static string getAwsTemporaryUrl(\League\Flysystem\AwsS3v3\AwsS3Adapter $adapter, string $path, \DateTimeInterface $expiration, array $options) Get a temporary URL for the file at the given path.
 * @method static void getRackspaceTemporaryUrl() Get a temporary URL for the file at the given path.
 * @method static array files(string | null $directory, bool $recursive) Get an array of all files in a directory.
 * @method static array allFiles(string | null $directory) Get all of the files from the given directory (recursive).
 * @method static array directories(string | null $directory, bool $recursive) Get all of the directories within a given directory.
 * @method static array allDirectories(string | null $directory) Get all (recursive) of the directories within a given directory.
 * @method static bool makeDirectory(string $path) Create a directory.
 * @method static bool deleteDirectory(string $directory) Recursively delete a directory.
 * @method static \League\Flysystem\FilesystemInterface getDriver() Get the Flysystem driver.
 *
 * @see \Illuminate\Filesystem\FilesystemManager
 */
class Storage extends Facade
{
    /**
     * Replace the given disk with a local testing disk.
     *
     * @param  string|null  $disk
     *
     * @return void
     */
    public static function fake($disk = null)
    {
        $disk = $disk ?: self::$app['config']->get('filesystems.default');

        (new Filesystem)->cleanDirectory(
            $root = storage_path('framework/testing/disks/'.$disk)
        );

        static::set($disk, self::createLocalDriver(['root' => $root]));
    }

    /**
     * Replace the given disk with a persistent local testing disk.
     *
     * @param  string|null  $disk
     * @return void
     */
    public static function persistentFake($disk = null)
    {
        $disk = $disk ?: self::$app['config']->get('filesystems.default');

        static::set($disk, self::createLocalDriver([
            'root' => storage_path('framework/testing/disks/'.$disk),
        ]));
    }

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'filesystem';
    }
}
