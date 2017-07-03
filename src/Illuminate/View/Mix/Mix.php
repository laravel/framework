<?php

namespace Illuminate\View\Mix;

use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;

class Mix
{
    /**
     * The sanitized path to the asset.
     *
     * @var string
     */
    protected $path;

    /**
     * The cached manifests.
     *
     * @var array
     */
    protected $cachedManifests = [];

    /**
     * The directory where the assets and the manifest file are.
     *
     * @var string
     */
    protected $manifestDirectory;

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

        return $this->getRealPath($path, $manifestDirectory);
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
        $this->init($path, $manifestDirectory);

        if ($this->hmrModeEnabled()) {
            return $this->getHmrPath();
        }

        return $this->getCompiledPath();
    }

    /**
     * Set a sanitized version of assets.
     *
     * @param  string  $path
     * @param  string  $manifestDirectory
     * @return void
     */
    protected function init($path, $manifestDirectory)
    {
        $this->path = $this->sanitize($path);
        $this->manifestDirectory = $this->sanitize($manifestDirectory);
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
     * @return bool
     */
    protected function hmrModeEnabled()
    {
        return file_exists(public_path($this->manifestDirectory.$this->hmrFilename));
    }

    /**
     * Get the full path to the file through the HMR server.
     *
     * @return \Illuminate\Support\HtmlString
     */
    protected function getHmrPath()
    {
        return new HtmlString($this->hmrURI.$this->path);
    }

    /**
     * Get the full path to the compiled file.
     *
     * @return \Illuminate\Support\HtmlString
     */
    protected function getCompiledPath()
    {
        return new HtmlString($this->manifestDirectory.$this->getPathFromManifest());
    }

    /**
     * Get the path from the manifest file.
     *
     * @return string
     *
     * @throws \Illuminate\View\Mix\MixException
     */
    protected function getPathFromManifest()
    {
        $manifest = $this->getManifest();

        if (array_key_exists($this->path, $manifest)) {
            return $manifest[$this->path];
        }

        throw new MixException(
            "Unable to locate the file: $this->path. Please check your ".
            'webpack.mix.js output paths and try again.'
        );
    }

    /**
     * Load the manifest file.
     *
     * @return array
     */
    protected function getManifest()
    {
        $manifestPath = public_path($this->manifestDirectory . $this->manifestFilename);

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
