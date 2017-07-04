<?php

namespace Illuminate\View\Mix;

use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;

class Mix
{
    /**
     * The cached manifests.
     *
     * @var array
     */
    protected $cachedManifests = [];

    /**
     * The cache of mix state.
     *
     * @var bool
     */
    protected $disabled = false;

    /**
     * The URI of HMR server.
     *
     * @var string
     */
    protected $hmrURI = '//localhost:8080';

    /**
     * The name of file which prove that HMR is enabled.
     *
     * @var string
     */
    protected $hmrFilename = '/hot';

    /**
     * The name of Mix Manifest file.
     *
     * @var string
     */
    protected $manifestFilename = '/mix-manifest.json';

    /**
     * Get the path to a versioned Mix asset or a simple message if mix is disabled.
     *
     * @param  string  $path
     * @param  string  $manifestDirectory
     * @return \Illuminate\Support\HtmlString
     */
    public function resolve($path, $manifestDirectory = '')
    {
        if ($this->disabled) {
            return $this->disabledPath();
        }

        return $this->getRealPath(
            $this->sanitize($path),
            $this->sanitize($manifestDirectory)
        );
    }

    /**
     * Get a message instead of the path when mix is disabled.
     *
     * @return \Illuminate\Support\HtmlString
     */
    protected function disabledPath()
    {
        return new HtmlString('Mix is disabled!');
    }

    /**
     * Get the path to a versioned Mix file.
     *
     * @param  string  $path
     * @param  string  $manifestDirectory
     * @return \Illuminate\Support\HtmlString
     */
    protected function getRealPath($path, $manifestDirectory)
    {
        if ($this->hmrModeEnabled($manifestDirectory)) {
            return $this->getHmrPath($path);
        }

        return $this->getCompiledPath($manifestDirectory, $path);
    }

    /**
     * Get a sanitized version of a path.
     *
     * @param  string  $path
     * @return string
     */
    protected function sanitize($path)
    {
        if (! Str::startsWith($path, '/')) {
            $path = "/{$path}";
        }

        return $path;
    }

    /**
     * Check if the HRM mode of Mix is enabled.
     *
     * @param  string  $manifestDirectory
     *
     * @return bool
     */
    protected function hmrModeEnabled($manifestDirectory)
    {
        return file_exists(public_path($manifestDirectory.$this->hmrFilename));
    }

    /**
     * Get the full path to the file through the HMR server.
     *
     * @param  string  $path
     *
     * @return \Illuminate\Support\HtmlString
     */
    protected function getHmrPath($path)
    {
        return new HtmlString($this->hmrURI.$path);
    }

    /**
     * Get the full path to the compiled file.
     *
     * @param  string  $manifestDirectory
     * @param  string  $path
     *
     * @return \Illuminate\Support\HtmlString
     */
    protected function getCompiledPath($manifestDirectory, $path)
    {
        return new HtmlString($manifestDirectory.$this->getPathFromManifest($manifestDirectory, $path));
    }

    /**
     * Get the path from the manifest file.
     *
     * @param  string  $manifestDirectory
     * @param  string  $path
     *
     * @return string
     * @throws \Illuminate\View\Mix\MixException
     */
    protected function getPathFromManifest($manifestDirectory, $path)
    {
        $manifest = $this->getManifest($manifestDirectory);

        if (array_key_exists($path, $manifest)) {
            return $manifest[$path];
        }

        throw new MixException(
            "Unable to locate the file: $path. Please check your ".
            'webpack.mix.js output paths and try again.'
        );
    }

    /**
     * Load the manifest file.
     *
     * @param  string  $manifestDirectory
     *
     * @return array
     */
    protected function getManifest($manifestDirectory)
    {
        $manifestPath = public_path($manifestDirectory.$this->manifestFilename);

        if (! array_key_exists($manifestPath, $this->cachedManifests)) {
            $this->cacheNewManifest($manifestPath);
        }

        return $this->cachedManifests[$manifestPath];
    }

    /**
     * Cache a new manifest file.
     *
     * @param  string  $manifestPath
     * @return void
     *
     * @throws \Illuminate\View\Mix\MixException
     */
    protected function cacheNewManifest($manifestPath)
    {
        if (! file_exists($manifestPath)) {
            throw new MixException("The Mix manifest $manifestPath does not exist.");
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new MixException("The Mix manifest $manifestPath isn't a proper json file.");
        }

        $this->cachedManifests[$manifestPath] = $manifest;
    }

    /**
     * Disable the mix function (in case of tests for example).
     *
     * @return $this
     */
    public function disable()
    {
        $this->disabled = true;

        return $this;
    }

    /**
     * Enable the mix function (in case of it was disabled before).
     *
     * @return $this
     */
    public function enable()
    {
        $this->disabled = false;

        return $this;
    }

    /**
     * Set the URI of HMR sever.
     *
     * @param  string  $hmrURI
     *
     * @return $this
     */
    public function setHmrURI($hmrURI)
    {
        $this->hmrURI = $hmrURI;

        return $this;
    }

    /**
     * Set the Mix Manifest filename.
     *
     * @param  string  $manifestFilename
     *
     * @return $this
     */
    public function setManifestFilename($manifestFilename)
    {
        $this->manifestFilename = $this->sanitize($manifestFilename);

        return $this;
    }

    /**
     * Set the HMR hot filename.
     *
     * @param  string  $hmrFilename
     *
     * @return $this
     */
    public function setHmrFilename($hmrFilename)
    {
        $this->hmrFilename = $this->sanitize($hmrFilename);

        return $this;
    }
}
