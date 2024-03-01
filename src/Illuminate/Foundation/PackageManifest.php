<?php

namespace Illuminate\Foundation;

use Exception;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Env;

class PackageManifest
{
    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    public $files;

    /**
     * The base path.
     *
     * @var string
     */
    public $basePath;

    /**
     * The vendor path.
     *
     * @var string
     */
    public $vendorPath;

    /**
     * The manifest path.
     *
     * @var string|null
     */
    public $manifestPath;

    /**
     * The loaded manifest array.
     *
     * @var array
     */
    public $manifest;

    /**
     * Create a new package manifest instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  string  $basePath
     * @param  string  $manifestPath
     * @return void
     */
    public function __construct(Filesystem $files, $basePath, $manifestPath)
    {
        $this->files = $files;
        $this->basePath = $basePath;
        $this->manifestPath = $manifestPath;
        $this->vendorPath = Env::get('COMPOSER_VENDOR_DIR') ?: $basePath.'/vendor';
    }

    /**
     * Get all of the service provider class names for all packages.
     *
     * @return array
     */
    public function providers()
    {
        return $this->config('providers');
    }

    /**
     * Get all of the aliases for all packages.
     *
     * @return array
     */
    public function aliases()
    {
        return $this->config('aliases');
    }

    /**
     * Get all of the values for all packages for the given configuration name.
     *
     * @param  string  $key
     * @return array
     */
    public function config($key)
    {
        return collect($this->getManifest())->flatMap(function ($configuration) use ($key) {
            return (array) ($configuration[$key] ?? []);
        })->filter()->all();
    }

    /**
     * Get the current package manifest.
     *
     * @return array
     */
    protected function getManifest()
    {
        if (! is_null($this->manifest)) {
            return $this->manifest;
        }

        if (! is_file($this->manifestPath)) {
            $this->build();
        }

        return $this->manifest = is_file($this->manifestPath) ?
            $this->files->getRequire($this->manifestPath) : [];
    }

    /**
     * Build the manifest and write it to disk.
     *
     * @return void
     */
    public function build()
    {
        $packages = $this->getInstalledPackages();

        $ignore = $this->packagesToIgnore();
        $ignoreAll = in_array('*', $ignore);

        $configurations = $this->getConfigurations($packages, $ignore, $ignoreAll);

        $this->write($configurations);
    }

    /**
     * Get the installed packages for the application.
     *
     * @return array|mixed
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function getInstalledPackages()
    {
        $path = $this->vendorPath.'/composer/installed.json';

        if ($this->files->exists($path)) {
            $installed = json_decode($this->files->get($path), true);
            return $installed['packages'] ?? $installed;
        }

        return [];
    }

    /**
     * Get the configurations for all the packages.
     *
     * @param  array  $packages
     * @param  array  $ignore
     * @param  bool   $ignoreAll
     * @return array
     */
    protected function getConfigurations(array $packages, array $ignore, bool $ignoreAll)
    {
        return collect($packages)
            ->mapWithKeys(function ($package) {
                return [$this->format($package['name']) => $package['extra']['laravel'] ?? []];
            })
            ->each(function ($configuration) use (&$ignore) {
                $ignore = array_merge($ignore, $configuration['dont-discover'] ?? []);
            })
            ->reject(function ($configuration, $package) use ($ignore, $ignoreAll) {
                return $ignoreAll || in_array($package, $ignore);
            })
            ->filter()
            ->all();
    }

    /**
     * Format the given package name.
     *
     * @param  string  $package
     * @return string
     */
    protected function format($package)
    {
        return str_replace($this->vendorPath.'/', '', $package);
    }

    /**
     * Get all of the package names that should be ignored.
     *
     * @return array
     */
    protected function packagesToIgnore()
    {
        if (! is_file($this->basePath.'/composer.json')) {
            return [];
        }

        return json_decode(file_get_contents(
            $this->basePath.'/composer.json'
        ), true)['extra']['laravel']['dont-discover'] ?? [];
    }

    /**
     * Write the given manifest array to disk.
     *
     * @param  array  $manifest
     * @return void
     *
     * @throws \Exception
     */
    protected function write(array $manifest)
    {
        if (! is_writable($dirname = dirname($this->manifestPath))) {
            throw new Exception("The {$dirname} directory must be present and writable.");
        }

        $this->files->replace(
            $this->manifestPath, '<?php return '.var_export($manifest, true).';'
        );
    }
}
