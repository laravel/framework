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
    protected $signature = 'package:discover {--details}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rebuild the cached package manifest';

    /**
     * Execute the console command.
     *
     * @param  \Illuminate\Foundation\PackageManifest
     * @return void
     */
    public function handle(PackageManifest $manifest)
    {
        $manifest->build();

        if($this->option('details')) {
            foreach ($manifest->manifest as $package => $details) {
                $this->line("<info>Discovered Package:</info> {$package}");
                if(isset($details['providers']) && count($details['providers'])) {
                    $this->line("<info>   Providers:</info>");
                    foreach($details['providers'] as $provider) {
                        $this->line("<info>     </info> {$provider}");
                    }
                }
                if(isset($details['aliases']) && count($details['aliases'])) {
                    $this->line("<info>   Aliases:</info>");
                    foreach($details['aliases'] as $alias) {
                        $this->line("<info>     </info> {$alias}");
                    }
                }
            }
        } else {
            foreach (array_keys($manifest->manifest) as $package) {

                $this->line("<info>Discovered Package:</info> {$package}");
            }
        }

        $this->info('Package manifest generated successfully.');
    }
}
