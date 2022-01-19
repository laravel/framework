<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class ConvertToApiCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'convert-to-api';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prepare the application for API usage';

    /**
     * Execute the console command.
     *
     * @return int|null
     */
    public function handle()
    {
        $files = new Filesystem;

        // Remove frontend asset related files and directories...
        $files->delete(base_path('package.json'));
        $files->delete(base_path('webpack.mix.js'));

        $files->deleteDirectory(base_path('resources/css'));
        $files->deleteDirectory(base_path('resources/js'));

        // Remove "web" routes file...
        $files->delete(base_path('routes/web.php'));

        // Adjust "views" directory...
        $files->delete(resource_path('views/welcome.blade.php'));
        $files->put(resource_path('views/.gitkeep'), PHP_EOL);

        // Install stubs...
        $files->copy(__DIR__.'/stubs/convert-to-api/AlwaysAcceptJsonResponses.stub', app_path('Http/Middleware/AlwaysAcceptJsonResponses.php'));
        $files->copy(__DIR__.'/stubs/convert-to-api/Handler.stub', app_path('Exceptions/Handler.php'));
        $files->copy(__DIR__.'/stubs/convert-to-api/Kernel.stub', app_path('Http/Kernel.php'));
        $files->copy(__DIR__.'/stubs/convert-to-api/RouteServiceProvider.stub', app_path('Providers/RouteServiceProvider.php'));
        $files->copy(__DIR__.'/stubs/convert-to-api/api.stub', base_path('routes/api.php'));

        // Remove Composer packages...
        $this->removeComposerPackages(['spatie/laravel-ignition'], $dev = true);
    }

    /**
     * Remove the given Composer Packages from the application.
     *
     * @param  array  $packages
     * @param  bool  $dev
     * @return void
     */
    protected function removeComposerPackages(array $packages, bool $dev = false)
    {
        $command = array_merge(
            array_values(array_filter(['composer', 'remove', $dev ? '--dev' : ''])),
            $packages
        );

        (new Process($command, base_path(), ['COMPOSER_MEMORY_LIMIT' => '-1']))
            ->setTimeout(null)
            ->run(function ($type, $output) {
                $this->output->write($output);
            });
    }
}
