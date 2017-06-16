<?php

namespace Illuminate\View\Mix;

use Illuminate\Support\HtmlString;

class Mix
{
    protected $path;
    protected $manifest;
    protected $manifestDirectory;
    protected $disabled = false;

    /**
     * Get the path to a versioned Mix file or a simple message if mix is disabled.
     *
     * @param  string  $path
     * @param  string  $manifestDirectory
     * @return \Illuminate\Support\HtmlString
     *
     * @throws \Illuminate\View\Mix\MixException
     */
    public function mix($path, $manifestDirectory = '')
    {

        if ($this->disabled) {
            return $this->disabledPath();
        }

        return $this->getRealPath($path, $manifestDirectory);
    }

    /**
     * Get the path to a versioned Mix file.
     *
     * @param  string  $path
     * @param  string  $manifestDirectory
     * @return \Illuminate\Support\HtmlString
     *
     * @throws \Illuminate\View\Mix\MixException
     */
    protected function getRealPath($path, $manifestDirectory)
    {
        $this->init($path, $manifestDirectory);

        if ($this->hmrModeEnabled()) {
            return $this->hmrPath();
        }

        return $this->compiledPath();
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
        if ($path && ! starts_with($path, '/')) {
            $path = "/{$path}";
        }

        return $path;
    }

    /**
     * Check if the HRM mode of Mix is enabled.
     *
     * @return boolean
     */
    protected function hmrModeEnabled()
    {
        return file_exists(public_path($this->manifestDirectory . '/hot'));
    }

    /**
     * Get the full path to the file through the HMR server.
     *
     * @return \Illuminate\Support\HtmlString
     */
    protected function hmrPath()
    {
        return new HtmlString("//localhost:8080{$this->path}");
    }

    /**
     * Get the full path to the compiled file.
     *
     * @return \Illuminate\Support\HtmlString
     *
     * @throws \Illuminate\View\Mix\MixException
     */
    protected function compiledPath()
    {
        return new HtmlString($this->manifestDirectory . $this->getPathFromManifest());
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
     * Get the path from the manifest file.
     *
     * @return string
     *
     * @throws \Illuminate\View\Mix\MixException
     */
    protected function getPathFromManifest()
    {
        return $this->getManifest()->get($this->path, function () {
            throw new MixException(
                "Unable to locate Mix file: {$this->path}. Please check your " .
                'webpack.mix.js output paths and try again.'
            );
        });
    }

    /**
     * Load the manifest file.
     *
     * @return \Illuminate\Support\Collection
     *
     * @throws \Illuminate\View\Mix\MixException
     */
    protected function getManifest()
    {
        if (!$this->manifest) {
            if (!file_exists($manifestPath = public_path($this->manifestDirectory . '/mix-manifest.json'))) {
                throw new MixException('The Mix manifest does not exist.');
            }

            $this->manifest = collect(json_decode(file_get_contents($manifestPath), true));
        }

        return $this->manifest;
    }

    /**
     * Disable the mix function (in case of tests for example).
     *
     * @param  boolean  $disabled
     * @return $this
     *
     */
    public function disable($disabled = true) {
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * Enable the mix function (in case of it was disabled before).
     *
     * @param  boolean  $enabled
     * @return $this
     *
     */
    public function enable($enabled = true) {
        $this->disable(! $enabled);

        return $this;
    }

}
