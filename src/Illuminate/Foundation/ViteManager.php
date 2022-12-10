<?php

namespace Illuminate\Foundation;

use Illuminate\Contracts\Foundation\Vite as ViteContract;
use Illuminate\Support\Manager;
use InvalidArgumentException;

class ViteManager extends Manager implements ViteContract
{
    /**
     * The registered app factory.
     *
     * @var (callable(\Illuminate\Contracts\Foundation\Vite, string, array, \Illuminate\Contracts\Container\Container): \Illuminate\Contracts\Foundation\Vite)|null
     */
    protected $appFactory = null;

    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->config->get('vite.app', 'default');
    }

    /**
     * Create a new driver instance.
     *
     * @param  string  $driver
     * @return \Illuminate\Contracts\Foundation\Vite
     */
    protected function createDriver($driver)
    {
        try {
            return parent::createDriver($driver);
        } catch (InvalidArgumentException) {
            return $this->createApp($driver);
        }
    }

    /**
     * Create a new app instance.
     *
     * @param  string  $app
     * @return \Illuminate\Contracts\Foundation\Vite
     */
    protected function createApp($app)
    {
        return ($this->appFactory ?? function (ViteContract $vite, string $app, array $config) {
            return $vite->configure($config);
        })(new Vite, $app, config("vite.apps.$app", []), $this->container);
    }

    /**
     * Register an app factory callback.
     *
     * @param  callable(\Illuminate\Contracts\Foundation\Vite, string, array, \Illuminate\Contracts\Container\Container): \Illuminate\Contracts\Foundation\Vite  $appFactory
     * @return $this
     */
    public function useAppFactory($appFactory)
    {
        $this->appFactory = $appFactory;

        return $this;
    }

    /**
     * Get an app instance.
     *
     * @param  string|null  $app
     * @return \Illuminate\Contracts\Foundation\Vite
     *
     * @throws \InvalidArgumentException
     */
    public function app($app = null)
    {
        return $this->driver($app);
    }

    /**
     * Apply configuration to the Vite instance.
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Foundation\Vite
     */
    public function configure($config)
    {
        return $this->app()->configure($config);
    }

    /**
     * Get the preloaded assets.
     *
     * @return array
     */
    public function preloadedAssets()
    {
        return $this->app()->preloadedAssets();
    }

    /**
     * Get the Content Security Policy nonce applied to all generated tags.
     *
     * @return string|null
     */
    public function cspNonce()
    {
        return $this->app()->cspNonce();
    }

    /**
     * Generate or set a Content Security Policy nonce to apply to all generated tags.
     *
     * @param  string|null  $nonce
     * @return string
     */
    public function useCspNonce($nonce = null)
    {
        return $this->app()->useCspNonce($nonce);
    }

    /**
     * Use the given key to detect integrity hashes in the manifest.
     *
     * @param  string|false  $key
     * @return \Illuminate\Contracts\Foundation\Vite
     */
    public function useIntegrityKey($key)
    {
        return $this->app()->useIntegrityKey($key);
    }

    /**
     * Set the Vite entry points.
     *
     * @param  array  $entryPoints
     * @return \Illuminate\Contracts\Foundation\Vite
     */
    public function withEntryPoints($entryPoints)
    {
        return $this->app()->withEntryPoints($entryPoints);
    }

    /**
     * Set the filename for the manifest file.
     *
     * @param  string  $filename
     * @return \Illuminate\Contracts\Foundation\Vite
     */
    public function useManifestFilename($filename)
    {
        return $this->app()->useManifestFilename($filename);
    }

    /**
     * Get the Vite "hot" file path.
     *
     * @return string
     */
    public function hotFile(): string
    {
        return $this->app()->hotFile();
    }

    /**
     * Set the Vite "hot" file path.
     *
     * @param  string  $path
     * @return \Illuminate\Contracts\Foundation\Vite
     */
    public function useHotFile($path)
    {
        return $this->app()->useHotFile($path);
    }

    /**
     * Set the Vite build directory.
     *
     * @param  string  $path
     * @return \Illuminate\Contracts\Foundation\Vite
     */
    public function useBuildDirectory($path)
    {
        return $this->app()->useBuildDirectory($path);
    }

    /**
     * Use the given callback to resolve attributes for script tags.
     *
     * @param  (callable(string, string, ?array, ?array): array)|array  $attributes
     * @return \Illuminate\Contracts\Foundation\Vite
     */
    public function useScriptTagAttributes($attributes)
    {
        return $this->app()->useScriptTagAttributes($attributes);
    }

    /**
     * Use the given callback to resolve attributes for style tags.
     *
     * @param  (callable(string, string, ?array, ?array): array)|array  $attributes
     * @return \Illuminate\Contracts\Foundation\Vite
     */
    public function useStyleTagAttributes($attributes)
    {
        return $this->app()->useStyleTagAttributes($attributes);
    }

    /**
     * Use the given callback to resolve attributes for preload tags.
     *
     * @param  (callable(string, string, ?array, ?array): array)|array  $attributes
     * @return \Illuminate\Contracts\Foundation\Vite
     */
    public function usePreloadTagAttributes($attributes)
    {
        return $this->app()->usePreloadTagAttributes($attributes);
    }

    /**
     * Generate Vite tags for an entrypoint.
     *
     * @param  string|string[]  $entrypoints
     * @param  string|null  $buildDirectory
     * @return \Illuminate\Support\HtmlString
     *
     * @throws \Exception
     */
    public function __invoke($entrypoints, $buildDirectory = null)
    {
        return $this->app()->__invoke($entrypoints, $buildDirectory);
    }

    /**
     * Generate React refresh runtime script.
     *
     * @return \Illuminate\Support\HtmlString|void
     */
    public function reactRefresh()
    {
        return $this->app()->reactRefresh();
    }

    /**
     * Get the URL for an asset.
     *
     * @param  string  $asset
     * @param  string|null  $buildDirectory
     * @return string
     */
    public function asset($asset, $buildDirectory = null)
    {
        return $this->app()->asset($asset, $buildDirectory);
    }

    /**
     * Get a unique hash representing the current manifest, or null if there is no manifest.
     *
     * @param  string|null  $buildDirectory
     * @return string|null
     */
    public function manifestHash($buildDirectory = null)
    {
        return $this->app()->manifestHash($buildDirectory);
    }

    /**
     * Determine if the HMR server is running.
     *
     * @return bool
     */
    public function isRunningHot()
    {
        return $this->app()->isRunningHot();
    }

    /**
     * Get the Vite tag content as a string of HTML.
     *
     * @return string
     */
    public function toHtml()
    {
        return $this->app()->toHtml();
    }
}
