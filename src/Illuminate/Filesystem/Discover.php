<?php

namespace Illuminate\Filesystem;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionException;

class Discover
{
    /**
     * Project path to look for.
     *
     * @var string
     */
    protected string $root;

    /**
     * The path from the base directory to look for classes.
     *
     * @var string|null
     */
    protected $dir = '';

    /**
     * The base directory to look for files.
     *
     * @var string|null
     */
    protected $baseDir = '';

    /**
     * The base namespace of the discovered files.
     *
     * @var string
     */
    protected string $baseNamespace = '';

    /**
     * How much to traverse the path being discovered.
     *
     * @var bool
     */
    protected $recursively = false;

    /**
     * Create a new Discover instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     */
    public function __construct(protected Application $app)
    {
        $this->root = $app->basePath();
    }

    /**
     * Sets the path to discover classes.
     *
     * @param  string  $dir
     * @return $this
     */
    public function in($dir)
    {
        $this->dir = $dir;

        return $this;
    }

    /**
     * Changes the base location and root namespace to discover files.
     *
     * @param  string  $baseDir
     * @param  string  $namespace
     * @return $this
     */
    public function atNamespace(string $baseDir, string $namespace)
    {
        $this->baseDir = $baseDir;
        $this->baseNamespace = $namespace;

        return $this;
    }

    /**
     * Discover all files from the directory path, recursively.
     *
     * @return $this
     */
    public function recursively()
    {
        $this->recursively = true;

        return $this;
    }

    /**
     * Returns all discovered classes.
     *
     * @return \Illuminate\Support\Collection<string, \ReflectionClass>
     */
    public function all()
    {
        $path = $this->normalizedPath();

        $classes = collect();

        $files = $this->recursively
            ? $this->app->make('files')->allFiles($path)
            : $this->app->make('files')->files($path);

        foreach ($files as $file) {
            try {
                $reflection = new ReflectionClass($this->classFromFile($file));
            } catch (ReflectionException) {
                continue;
            }

            if (!$reflection->isInstantiable()) {
                continue;
            }

            $classes->put($reflection->name, $reflection);
        }

        return $classes;
    }

    /**
     * Ensure there is a base directory and namespace to look for.
     *
     * @return string
     */
    protected function normalizedPath()
    {
        $this->baseDir = $this->baseDir ?: Str::of($this->app->path())
            ->after($this->root)
            ->ltrim(DIRECTORY_SEPARATOR)
            ->toString();

        $this->baseNamespace = Str::finish($this->baseNamespace ?: $this->app->getNamespace(), '\\');

        $path = $this->root.DIRECTORY_SEPARATOR.$this->baseDir;

        if ($this->dir) {
            $path .= DIRECTORY_SEPARATOR.$this->dir;
        }

        return $path;
    }

    /**
     * Extract the class name from the given file path.
     *
     * @param  \Symfony\Component\Finder\SplFileInfo  $file
     * @return string
     */
    protected function classFromFile($file)
    {
        $class = trim(Str::replaceFirst($this->root, '', $file->getRealPath()), DIRECTORY_SEPARATOR);

        return str_replace(
            [DIRECTORY_SEPARATOR, ucfirst($this->baseDir.'\\')],
            ['\\', $this->baseNamespace],
            ucfirst(Str::replaceLast('.php', '', $class))
        );
    }

    /**
     * Create a new instance of the discoverer.
     *
     * @return static
     */
    public static function inside(string $dir)
    {
        return (new static(app()))->in($dir);
    }
}
