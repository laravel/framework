<?php

namespace Illuminate\View\Mix;

use Illuminate\Support\HtmlString;

class Mix
{
    protected $manifest;
    protected $manifestDirectory;
    protected $path;

    /**
     * Get the path to a versioned Mix file.
     *
     * @param  string  $path
     * @param  string  $manifestDirectory
     * @return \Illuminate\Support\HtmlString
     *
     * @throws \Illuminate\View\Mix\MixException
     */
    public function mix($path, $manifestDirectory = '')
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
        if ($path && !starts_with($path, '/')) {
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
     */
    protected function compiledPath()
    {
        return new HtmlString($this->manifestDirectory . $this->getPathFromManifest());
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
        return data_get($this->getManifest(), $this->path, function () {
            throw new MixException(
                "Unable to locate Mix file: {$this->path}. Please check your " .
                'webpack.mix.js output paths and try again.'
            );
        });
    }

    /**
     * Load the manifest file.
     *
     * @return array
     *
     * @throws \Illuminate\View\Mix\MixException
     */
    protected function getManifest()
    {
        if (!$this->manifest) {
            if (!file_exists($manifestPath = public_path($this->manifestDirectory . '/mix-manifest.json'))) {
                throw new MixException('The Mix manifest does not exist.');
            }

            $this->manifest = json_decode(file_get_contents($manifestPath), true);
        }

        return $this->manifest;
    }

}
