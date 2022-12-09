<?php

namespace Illuminate\Contracts\Foundation;

use Illuminate\Contracts\Support\Htmlable;

interface Vite extends Htmlable
{
    /**
     * Apply configuration to the Vite instance.
     *
     * @param  array  $config
     * @return $this
     */
    public function configure($config);

    /**
     * Get the preloaded assets.
     *
     * @return array
     */
    public function preloadedAssets();

    /**
     * Get the Content Security Policy nonce applied to all generated tags.
     *
     * @return string|null
     */
    public function cspNonce();

    /**
     * Generate or set a Content Security Policy nonce to apply to all generated tags.
     *
     * @param  string|null  $nonce
     * @return string
     */
    public function useCspNonce($nonce = null);

    /**
     * Use the given key to detect integrity hashes in the manifest.
     *
     * @param  string|false  $key
     * @return $this
     */
    public function useIntegrityKey($key);

    /**
     * Set the Vite entry points.
     *
     * @param  array  $entryPoints
     * @return $this
     */
    public function withEntryPoints($entryPoints);

    /**
     * Set the filename for the manifest file.
     *
     * @param  string  $filename
     * @return $this
     */
    public function useManifestFilename($filename);

    /**
     * Get the Vite "hot" file path.
     *
     * @return string
     */
    public function hotFile();

    /**
     * Set the Vite "hot" file path.
     *
     * @param  string  $path
     * @return $this
     */
    public function useHotFile($path);

    /**
     * Set the Vite build directory.
     *
     * @param  string  $path
     * @return $this
     */
    public function useBuildDirectory($path);

    /**
     * Use the given callback to resolve attributes for script tags.
     *
     * @param  (callable(string, string, ?array, ?array): array)|array  $attributes
     * @return $this
     */
    public function useScriptTagAttributes($attributes);

    /**
     * Use the given callback to resolve attributes for style tags.
     *
     * @param  (callable(string, string, ?array, ?array): array)|array  $attributes
     * @return $this
     */
    public function useStyleTagAttributes($attributes);

    /**
     * Use the given callback to resolve attributes for preload tags.
     *
     * @param  (callable(string, string, ?array, ?array): array)|array  $attributes
     * @return $this
     */
    public function usePreloadTagAttributes($attributes);

    /**
     * Generate Vite tags for an entrypoint.
     *
     * @param  string|string[]  $entrypoints
     * @param  string|null  $buildDirectory
     * @return \Illuminate\Support\HtmlString
     *
     * @throws \Exception
     */
    public function __invoke($entrypoints, $buildDirectory = null);

    /**
     * Generate React refresh runtime script.
     *
     * @return \Illuminate\Support\HtmlString|void
     */
    public function reactRefresh();

    /**
     * Get the URL for an asset.
     *
     * @param  string  $asset
     * @param  string|null  $buildDirectory
     * @return string
     */
    public function asset($asset, $buildDirectory = null);

    /**
     * Get a unique hash representing the current manifest, or null if there is no manifest.
     *
     * @return string|null
     */
    public function manifestHash($buildDirectory = null);

    /**
     * Determine if the HMR server is running.
     *
     * @return bool
     */
    public function isRunningHot();
}
