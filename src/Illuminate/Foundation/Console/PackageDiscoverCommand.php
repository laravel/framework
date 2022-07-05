<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Console\View\Components\Task;
use Illuminate\Foundation\PackageManifest;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'package:discover')]
class PackageDiscoverCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'package:discover';

    /**
     * The name of the console command.
     *
     * This name is used to identify the command during lazy loading.
     *
     * @var string|null
     *
     * @deprecated
     */
    protected static $defaultName = 'package:discover';

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
        $this->info('Discovering packages');

        $manifest->build();

        collect($manifest->manifest)
            ->map(fn () => fn () => true)
            ->each(fn ($task, $description) => Task::renderUsing(
                $this->output, $description, $task,
            ));

        $this->newLine();
    }
}
