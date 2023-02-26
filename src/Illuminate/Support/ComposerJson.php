<?php

namespace Illuminate\Support;

use Exception;
use InvalidArgumentException;
use Symfony\Component\Finder\Finder;

class ComposerJson
{
    private $result = [];

    public $basePath = null;

    public $ignoredNamespaces = [];

    public static function make($folderPath, $ignoredNamespaces = [])
    {
        $folderPath = rtrim($folderPath, '/\\ ');
        $folderPath = str_replace('/\\', DIRECTORY_SEPARATOR, $folderPath);
        if (file_exists($folderPath.DIRECTORY_SEPARATOR.'composer.json')) {
            return new static($folderPath, $ignoredNamespaces);
        } else {
            throw new InvalidArgumentException('The path ('.$folderPath.') does not contain a composer.json file.');
        }
    }

    private function __construct($basePath, $ignoredNamespaces)
    {
        $this->basePath = $basePath;
        $this->ignoredNamespaces = $ignoredNamespaces;
    }

    public function readAutoload($purgeShortcuts = false)
    {
        $result = [];

        foreach ($this->collectLocalRepos() as $relativePath) {
            // We avoid autoload-dev for repositories.
            $result[$relativePath] = $this->readKey('autoload.psr-4', $relativePath);
        }

        // add the root composer.json
        $result['/'] = $this->readKey('autoload.psr-4');

        $results = $purgeShortcuts ? $this->purgeAutoloadShortcuts($result) : $result;

        return $this->removedIgnored($results, $this->ignoredNamespaces);
    }

    public function collectLocalRepos()
    {
        $composers = [];

        foreach ($this->readKey('repositories') as $repo) {
            if (($repo['type'] ?? '') !== 'path') {
                continue;
            }

            $dirPath = ltrim($repo['url'], '.\\/');

            $path = $this->basePath.DIRECTORY_SEPARATOR.$dirPath.DIRECTORY_SEPARATOR.'composer.json';

            // Sometimes php can not detect relative paths, so we use the absolute path here.
            if (file_exists($path)) {
                $composers[$dirPath] = $dirPath;
            }
        }

        return $composers;
    }

    public function readKey($key, $composerPath = '')
    {
        $composer = $this->readComposerFileData($composerPath);

        $value = Arr::get($composer, $key, []);

        if (\in_array($key, ['autoload.psr-4', 'autoload-dev.psr-4'])) {
            $value = $this->normalizePaths($value, $composerPath);
        }

        return $value;
    }

    /**
     * @param  string  $path
     * @return array
     */
    public function readComposerFileData($path = '')
    {
        $absPath = $this->basePath.DIRECTORY_SEPARATOR.$path;

        $absPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $absPath);

        // ensure it does not end with slash
        $absPath = rtrim($absPath, DIRECTORY_SEPARATOR);

        if (! isset($this->result[$absPath])) {
            $this->result[$absPath] = json_decode(file_get_contents($absPath.DIRECTORY_SEPARATOR.'composer.json'), true);
        }

        return $this->result[$absPath];
    }

    public function getClasslists(?\Closure $filter, ?\Closure $pathFilter)
    {
        $classLists = [];

        foreach ($this->readAutoload(true) as $composerFilePath => $autoload) {
            foreach ($autoload as $namespace => $psr4Path) {
                $classLists[$composerFilePath][$namespace] = $this->getClassesWithin($psr4Path, $filter, $pathFilter);
            }
        }

        return $classLists;
    }

    public function getClassesWithin($composerPath, \Closure $filterClass, ?\Closure $pathFilter = null)
    {
        $results = [];
        foreach ($this->getAllPhpFiles($composerPath) as $classFilePath) {
            $absFilePath = $classFilePath->getRealPath();
            $relativePath = str_replace(base_path(), '', $absFilePath);

            if ($pathFilter && ! $pathFilter($absFilePath, $relativePath, $classFilePath->getFilename())) {
                continue;
            }

            $class = str_replace('.php', '', $classFilePath->getFilename());

            // Exclude blade files
            if (substr_count($class, '.') !== 0) {
                continue;
            }

            $namespacedClass = $this->getNamespacedClassFromPath($absFilePath);

            if (! class_exists($namespacedClass)) {
                continue;
            }

            if ($filterClass($classFilePath, $namespacedClass, $class) === false) {
                continue;
            }

            $results[] = [
                'relativePath' => str_replace(base_path(), '', $absFilePath),
                'fileName' => $classFilePath->getFilename(),
                'currentNamespace' => $namespacedClass,
                'absFilePath' => $absFilePath,
                'class' => $class,
            ];
        }

        return $results;
    }

    /**
     * Checks all the psr-4 loaded classes to have correct namespace.
     *
     * @param  array  $autoloads
     * @return array
     */
    public function purgeAutoloadShortcuts($autoloads)
    {
        foreach ($autoloads as $composerPath => $psr4Mappings) {
            foreach ($psr4Mappings as $namespace1 => $psr4Path1) {
                foreach ($psr4Mappings as $psr4Path2) {
                    if (strlen($psr4Path1) > strlen($psr4Path2) && Str::startsWith($psr4Path1, $psr4Path2)) {
                        unset($autoloads[$composerPath][$namespace1]);
                    }
                }
            }
        }

        return $autoloads;
    }

    public function getNamespacedClassFromPath($absPath)
    {
        $psr4Mappings = $this->readAutoload();
        // Converts "absolute path" to "relative path":
        $relativePath = trim(str_replace($this->basePath, '', $absPath), '/\\');
        $className = str_replace('.php', '', basename($absPath));

        foreach ($psr4Mappings as $composerPath => $psr4Mapping) {
            if (str_starts_with($relativePath, $composerPath)) {
                $correctNamespaces = $this->getCorrectNamespaces($psr4Mapping, $relativePath);

                return $this->findShortest($correctNamespaces).'\\'.$className;
            }
        }

        $correctNamespaces = $this->getCorrectNamespaces($psr4Mappings['/'], $relativePath);

        return $this->findShortest($correctNamespaces).'\\'.$className;
    }

    /**
     * get all ".php" files in directory by giving a path.
     *
     * @param  string  $path  Directory path
     * @return \Symfony\Component\Finder\Finder
     */
    public function getAllPhpFiles($path, $basePath = '')
    {
        if ($basePath === '') {
            $basePath = $this->basePath;
        }

        $basePath = rtrim($basePath, '/\\');
        $path = ltrim($path, '/\\');
        $path = $basePath.DIRECTORY_SEPARATOR.$path;

        try {
            return Finder::create()->files()->name('*.php')->in($path);
        } catch (Exception) {
            return [];
        }
    }

    private function normalizePaths($value, $path)
    {
        $path && $path = Str::finish($path, '/');
        foreach ($value as $namespace => $_path) {
            if (is_array($_path)) {
                foreach ($_path as $i => $p) {
                    $value[$namespace][$i] = str_replace('//', '/', $path.Str::finish($p, '/'));
                }
            } else {
                $value[$namespace] = str_replace('//', '/', $path.Str::finish($_path, '/'));
            }
        }

        return $value;
    }

    private function removedIgnored($mapping, $ignored = [])
    {
        $result = [];

        foreach ($mapping as $i => $map) {
            foreach ($map as $namespace => $path) {
                if (! in_array($namespace, $ignored)) {
                    $result[$i][$namespace] = $path;
                }
            }
        }

        return $result;
    }

    private function findShortest($correctNamespaces)
    {
        return array_reduce($correctNamespaces, function ($a, $b) {
            return ($a === null || strlen($a) >= strlen($b)) ? $b : $a;
        });
    }

    private function getCorrectNamespaces($psr4Mapping, $relativePath)
    {
        $correctNamespaces = [];
        $relativePath = str_replace(['\\', '.php'], ['/', ''], $relativePath);
        foreach ($psr4Mapping as $namespacePrefix => $paths) {
            foreach ((array) $paths as $path) {
                if (str_starts_with($relativePath, $path)) {
                    $correctNamespace = substr_replace($relativePath, $namespacePrefix, 0, strlen($path));
                    $correctNamespace = str_replace('/', '\\', $correctNamespace);
                    $correctNamespaces[] = $this->getNamespaceFromFullClass($correctNamespace);
                }
            }
        }

        return $correctNamespaces;
    }

    private function getNamespaceFromFullClass($class)
    {
        $segments = explode('\\', $class);
        array_pop($segments); // removes the last part

        return trim(implode('\\', $segments), '\\');
    }
}
