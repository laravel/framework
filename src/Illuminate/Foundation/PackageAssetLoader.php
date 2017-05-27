<?php

namespace Illuminate\Foundation;

use Illuminate\Filesystem\Filesystem;

class PackageAssetLoader
{
    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $directory;

    public function __construct(Filesystem $filesystem, string $directory)
    {
        $this->filesystem = $filesystem;
        $this->directory = $directory;
    }

    public function get($key)
    {
        $assets = [];

        foreach ($this->filesystem->directories($this->directory) as $vendor) {
            foreach ($this->filesystem->directories($vendor) as $package) {
                $config = json_decode($this->filesystem->get($package . '/composer.json'), true);

                $assets = array_merge($assets, (array) ($config['extra'][$key] ?? []));
            }
        }

        return array_unique($assets);
    }
}
