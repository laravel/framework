<?php

namespace Illuminate\Filesystem;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Finder\SplFileInfo;

class Discover
{
    /**
     * Project path where all discoveries will be done.
     *
     * @var string
     */
    protected string $projectPath;

    /**
     * The base path to discover classes.
     *
     * @var string
     */
    protected string $basePath;

    /**
     * The base namespace to discover classes.
     *
     * @var string
     */
    protected string $baseNamespace;

    /**
     * If the discovery should explore deeper directories.
     *
     * @var bool
     */
    protected bool $recursive = false;

    /**
     * Create a new Discover instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  string  $path
     */
    public function __construct(Application $app, protected string $path)
    {
        $this->projectPath = $app->basePath();
        $this->baseNamespace = $app->getNamespace();
        $this->basePath = Str::of($app->path())->after($this->projectPath)->trim(DIRECTORY_SEPARATOR);
    }

    /**
     * Changes the base location and root namespace to discover files.
     *
     * @param  string  $baseNamespace
     * @param  string|null  $basePath
     * @return $this
     */
    public function atNamespace(string $baseNamespace, string $basePath = null): static
    {
        $this->baseNamespace = Str::of($baseNamespace)->ucfirst()->trim('\\')->finish('\\');
        $this->basePath = trim($basePath ?: lcfirst($baseNamespace), DIRECTORY_SEPARATOR);

        return $this;
    }

    /**
     * Search of files recursively.
     *
     * @return $this
     */
    public function recursively(): static
    {
        $this->recursive = true;

        return $this;
    }

    /**
     * Returns a Collection for all the classes found.
     *
     * @return \Illuminate\Support\Collection<string, \ReflectionClass>
     */
    public function all(): Collection
    {
        $classes = new Collection;

        foreach ($this->listAllFiles() as $file) {
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
     * Builds the finder instance to locate the files.
     *
     * @return \Illuminate\Support\Collection<int, \Symfony\Component\Finder\SplFileInfo>
     */
    protected function listAllFiles(): Collection
    {
        return new Collection(
            $this->recursive
                ? File::allFiles($this->buildPath())
                : File::files($this->buildPath())
        );
    }

    /**
     * Build the path to search for files.
     *
     * @return string
     */
    protected function buildPath(): string
    {
        return Str::of($this->path)
            ->start(DIRECTORY_SEPARATOR)
            ->prepend($this->basePath)
            ->start(DIRECTORY_SEPARATOR)
            ->prepend($this->projectPath);
    }

    /**
     * Create a new instance of the discoverer.
     *
     * @param  string  $dir
     * @return static
     */
    public static function in(string $dir): static
    {
        return new static(app(), $dir);
    }

    /**
     * Extract the class name from the given file path.
     *
     * @param  \Symfony\Component\Finder\SplFileInfo  $file
     * @return string
     */
    protected function classFromFile(SplFileInfo $file): string
    {
        return Str::of($file->getRealPath())
            ->after($this->projectPath)
            ->trim(DIRECTORY_SEPARATOR)
            ->beforeLast('.php')
            ->ucfirst()
            ->replace(
                [DIRECTORY_SEPARATOR, ucfirst($this->basePath.DIRECTORY_SEPARATOR)],
                ['\\', $this->baseNamespace],
            );
    }
}
