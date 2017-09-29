<?php

namespace Illuminate\Support\Facades;

/**
 * @method static bool exists(string $path) Determine if a file or directory exists.
 * @method static string get(string $path, bool $lock) Get the contents of a file.
 * @method static string sharedGet(string $path) Get contents of a file with shared access.
 * @method static mixed getRequire(string $path) Get the returned value of a file.
 * @method static mixed requireOnce(string $file) Require the given file once.
 * @method static string hash(string $path) Get the MD5 hash of the file at the given path.
 * @method static int put(string $path, string $contents, bool $lock) Write the contents of a file.
 * @method static int prepend(string $path, string $data) Prepend to a file.
 * @method static int append(string $path, string $data) Append to a file.
 * @method static mixed chmod(string $path, int $mode) Get or set UNIX mode of a file or directory.
 * @method static bool delete(string | array $paths) Delete the file at a given path.
 * @method static bool move(string $path, string $target) Move a file to a new location.
 * @method static bool copy(string $path, string $target) Copy a file to a new location.
 * @method static void link(string $target, string $link) Create a hard link to the target file or directory.
 * @method static string name(string $path) Extract the file name from a file path.
 * @method static string basename(string $path) Extract the trailing name component from a file path.
 * @method static string dirname(string $path) Extract the parent directory from a file path.
 * @method static string extension(string $path) Extract the file extension from a file path.
 * @method static string type(string $path) Get the file type of a given file.
 * @method static string|false mimeType(string $path) Get the mime-type of a given file.
 * @method static int size(string $path) Get the file size of a given file.
 * @method static int lastModified(string $path) Get the file's last modification time.
 * @method static bool isDirectory(string $directory) Determine if the given path is a directory.
 * @method static bool isReadable(string $path) Determine if the given path is readable.
 * @method static bool isWritable(string $path) Determine if the given path is writable.
 * @method static bool isFile(string $file) Determine if the given path is a file.
 * @method static array glob(string $pattern, int $flags) Find path names matching a given pattern.
 * @method static array files(string $directory, bool $hidden) Get an array of all files in a directory.
 * @method static array allFiles(string $directory, bool $hidden) Get all of the files from the given directory (recursive).
 * @method static array directories(string $directory) Get all of the directories within a given directory.
 * @method static bool makeDirectory(string $path, int $mode, bool $recursive, bool $force) Create a directory.
 * @method static bool moveDirectory(string $from, string $to, bool $overwrite) Move a directory.
 * @method static bool copyDirectory(string $directory, string $destination, int $options) Copy a directory from one location to another.
 * @method static bool deleteDirectory(string $directory, bool $preserve) Recursively delete a directory.
 * @method static bool cleanDirectory(string $directory) Empty the specified directory of all files and folders.
 * @method static void macro(string $name, object | callable $macro) Register a custom macro.
 * @method static void mixin(object $mixin) Mix another object into the class.
 * @method static bool hasMacro(string $name) Checks if macro is registered.
 *
 * @see \Illuminate\Filesystem\Filesystem
 */
class File extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'files';
    }
}
