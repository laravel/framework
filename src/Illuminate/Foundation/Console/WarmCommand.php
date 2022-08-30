<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\WarmerCollection;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'warm')]
class WarmCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'warm {--exclude= : The registered warmers to exclude} {--only= : Only run the specified registered warmers}';

    /**
     * The name of the console command.
     *
     * This name is used to identify the command during lazy loading.
     *
     * @var string|null
     *
     * @deprecated
     */
    protected static $defaultName = 'warm';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Warm the application.';

    /**
     * Execute the console command.
     *
     * @param  \Illuminate\Support\WarmerCollection  $warmers
     * @param  \Illuminate\Contracts\Console\Kernel  $kernel
     * @return int
     */
    public function handle(WarmerCollection $warmers, Kernel $kernel)
    {
        $warmers
            ->forget($this->excluded())
            ->only($this->only())
            ->when(method_exists($kernel, 'warmResolver'))->merge([
                'The application: '.config('app.name') => $kernel->warmResolver()
            ])
            ->each(fn ($callable, $name) => $this->warm($name, $callable));

        $this->components->info('Your application has finished warming.');

        return self::SUCCESS;
    }

    /**
     * Run the wamer.
     *
     * @param  string  $name
     * @param  callable  $callable
     * @return void
     */
    protected function warm($name, $callable)
    {
        $this->components->task($name, fn () => $this->laravel->call($callable));
    }

    /**
     * The registered warmers to exclude from running.
     *
     * @return array
     */
    protected function excluded()
    {
        return explode(',', $this->option('exclude') ?? '');
    }

    /**
     * The registered warmers that should be run.
     *
     * @return null|array
     */
    protected function only()
    {
        if ($this->option('only') === null) {
            return null;
        }

        return explode(',', $this->option('only'));
    }
}
