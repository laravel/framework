<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Foundation\PackageManifest;

class PackageDiscoverCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'package:discover';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rebuild the cached package manifest';

    /**
     * Execute the console command.
     *
     * @param  \Illuminate\Foundation\PackageManifest  $manifest
     * @return void
     */
    public function handle(PackageManifest $manifest)
    {
        $previousPackages = array_keys($manifest->previousManifest ?? []);

        $manifest->build();

        foreach (array_keys($manifest->manifest) as $package) {
            $new = in_array($package, $previousPackages) ? '' : ' <comment>(new)</comment>';

            $this->line("Discovered Package: <info>{$package}</info>{$new}");
        }

        $manifest->storePreviousManifest();

        $this->info('Package manifest generated successfully.');
    }
}
