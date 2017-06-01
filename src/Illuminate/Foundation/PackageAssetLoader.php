<?php

namespace Illuminate\Foundation;

use Exception;
use Illuminate\Filesystem\Filesystem;

class PackageAssetLoader
{
    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    private $files;

    /**
     * @var string
     */
    private $vendorPath;

    /**
     * @var string|null
     */
    private $manifestPath;

    public function __construct(Filesystem $files, string $vendorPath, string $manifestPath = null)
    {
        $this->files = $files;
        $this->vendorPath = $vendorPath;
        $this->manifestPath = $manifestPath;
    }

    public function get(string $key): array
    {
        $manifest = [];

        if (file_exists($this->manifestPath)) {
            $manifest = $this->files->getRequire($this->manifestPath);

            // If the manifest has a key for the given asset type,
            // we'll simply return the assets without loading
            // all of the assets again from the packages.
            if (isset($manifest[$key])) {
                return $manifest[$key];
            }
        }

        $manifest[$key] = $this->retrieveAssets($key);

        if ($this->manifestPath) {
            $this->writeManifest($manifest);
        }

        return $manifest[$key];
    }

    private function retrieveAssets(string $key)
    {
        $assets = [];

        foreach ($this->files->directories($this->vendorPath) as $vendor) {
            foreach ($this->files->directories($vendor) as $package) {
                $config = json_decode($this->files->get($package.'/composer.json'), true);

                $assets = array_merge($assets, (array) ($config['extra'][$key] ?? []));
            }
        }

        return array_unique($assets);
    }

    private function writeManifest(array $manifest)
    {
        if (! is_writable(dirname($this->manifestPath))) {
            throw new Exception('The bootstrap/cache directory must be present and writable.');
        }

        $this->files->put(
            $this->manifestPath, '<?php return '.var_export($manifest, true).';'
        );
    }
}
