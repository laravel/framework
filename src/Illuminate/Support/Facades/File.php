<?php

namespace Illuminate\Support\Facades;

/**
 * @method static \Symfony\Component\Finder\SplFileInfo[] allFiles(string $directory, bool $hidden = false)
 * @method static int append(string $path, string $data)
 * @method static string basename(string $path)
 * @method static mixed chmod(string $path, int|null $mode = null)
 * @method static bool cleanDirectory(string $directory)
 * @method static bool copy(string $path, string $target)
 * @method static bool copyDirectory(string $directory, string $destination, int|null $options = null)
 * @method static bool delete(string|array $paths)
 * @method static bool deleteDirectories(string $directory)
 * @method static bool deleteDirectory(string $directory, bool $preserve = false)
 * @method static array directories(string $directory)
 * @method static string dirname(string $path)
 * @method static void ensureDirectoryExists(string $path, int $mode = 493, bool $recursive = true)
 * @method static bool exists(string $path)
 * @method static string extension(string $path)
 * @method static \Symfony\Component\Finder\SplFileInfo[] files(string $directory, bool $hidden = false)
 * @method static void flushMacros()
 * @method static string get(string $path, bool $lock = false)
 * @method static mixed getRequire(string $path, array $data = [])
 * @method static array glob(string $pattern, int $flags = 0)
 * @method static string|null guessExtension(string $path)
 * @method static bool hasMacro(string $name)
 * @method static bool hasSameHash(string $firstFile, string $secondFile)
 * @method static string hash(string $path)
 * @method static bool isDirectory(string $directory)
 * @method static bool isEmptyDirectory(string $directory, bool $ignoreDotFiles = false)
 * @method static bool isFile(string $file)
 * @method static bool isReadable(string $path)
 * @method static bool isWritable(string $path)
 * @method static int lastModified(string $path)
 * @method static \Illuminate\Support\LazyCollection lines(string $path)
 * @method static void link(string $target, string $link)
 * @method static void macro(string $name, object|callable $macro)
 * @method static bool makeDirectory(string $path, int $mode = 493, bool $recursive = false, bool $force = false)
 * @method static string|false mimeType(string $path)
 * @method static bool missing(string $path)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool move(string $path, string $target)
 * @method static bool moveDirectory(string $from, string $to, bool $overwrite = false)
 * @method static string name(string $path)
 * @method static int prepend(string $path, string $data)
 * @method static int|bool put(string $path, string $contents, bool $lock = false)
 * @method static void relativeLink(string $target, string $link)
 * @method static void replace(string $path, string $content)
 * @method static void replaceInFile(array|string $search, array|string $replace, string $path)
 * @method static mixed requireOnce(string $path, array $data = [])
 * @method static string sharedGet(string $path)
 * @method static int size(string $path)
 * @method static string type(string $path)
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
