<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

#[AsCommand(name: 'view:cache')]
class ViewCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'view:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Compile all of the application's Blade templates";

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $latestCachedTimestamp = $this->latestCachedTimestamp();

        $this->paths()->each(function ($path) {
            $this->compileViewsIn($path);
        });

        $this->newLine();

        $this->components->info('Blade templates cached successfully.');

        $newLatestCachedTimestamp = $this->latestCachedTimestamp();

        if (! is_null($latestCachedTimestamp) &&
            ! is_null($newLatestCachedTimestamp) &&
            $latestCachedTimestamp < $newLatestCachedTimestamp) {
            $this->pruneViewsCachedBefore($newLatestCachedTimestamp);
        }
    }

    /**
     * Compile the view files in the given directory.
     *
     * @param  \Illuminate\Support\Collection  $views
     * @return void
     */
    protected function compileViewsIn($path)
    {
        $prefix = $this->output->isVeryVerbose() ? '<fg=yellow;options=bold>DIR</> ' : '';

        $this->components->task($prefix.$path, null, OutputInterface::VERBOSITY_VERBOSE);

        $this->compileViews($this->bladeFilesIn([$path]));
    }

    /**
     * Compile the given view files.
     *
     * @param  \Illuminate\Support\Collection  $views
     * @return void
     */
    protected function compileViews(Collection $views)
    {
        $compiler = $this->laravel['view']->getEngineResolver()->resolve('blade')->getCompiler();

        $views->map(function (SplFileInfo $file) use ($compiler) {
            $this->components->task('    '.$file->getRelativePathname(), null, OutputInterface::VERBOSITY_VERY_VERBOSE);

            $compiler->compile($file->getRealPath());
        });

        if ($this->output->isVeryVerbose()) {
            $this->newLine();
        }
    }

    /**
     * Get the Blade files in the given path.
     *
     * @param  array  $paths
     * @return \Illuminate\Support\Collection
     */
    protected function bladeFilesIn(array $paths)
    {
        return collect(
            Finder::create()
                ->in($paths)
                ->exclude('vendor')
                ->name('*.blade.php')
                ->files()
        );
    }

    /**
     * Get all of the possible view paths.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function paths()
    {
        $finder = $this->laravel['view']->getFinder();

        return collect($finder->getPaths())->merge(
            collect($finder->getHints())->flatten()
        );
    }

    /**
     * Prune all views cached before the given timestamp.
     *
     * @param  int  $timestamp
     * @return void
     */
    protected function pruneViewsCachedBefore($timestamp)
    {
        return $this->cachedViews()->each(function ($file) use ($timestamp) {
            if ($file->getMTime() < $timestamp) {
                @unlink($file->getRealPath());
            }
        });
    }

    /**
     * Get the timestamp of the latest cached view.
     *
     * @return int
     */
    protected function latestCachedTimestamp()
    {
        return $this->cachedViews()->map->getMTime()->max();
    }

    /**
     * Get a collection of the currently cached views.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function cachedViews()
    {
        return collect(
            Finder::create()
                ->in([$this->laravel['config']->get('view.compiled')])
                ->name('*.php')
                ->files()
        );
    }
}
