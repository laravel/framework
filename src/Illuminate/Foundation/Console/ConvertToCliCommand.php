<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class ConvertToCliCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'convert-to-cli';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prepare the application for CLI usage';

    /**
     * Execute the console command.
     *
     * @return int|null
     */
    public function handle()
    {
        $files = new Filesystem;

        // Remove "Http" directory...
        $files->deleteDirectory(app_path('Http'));

        // Remove "Models" directory...
        $files->deleteDirectory(app_path('Models'));

        // Remove unnecessary providers...
        $files->delete(app_path('Providers/AuthServiceProvider.php'));
        $files->delete(app_path('Providers/BroadcastServiceProvider.php'));
        $files->delete(app_path('Providers/RouteServiceProvider.php'));

        // Remove unnecessary configuration files...
        $files->delete(config_path('auth.php'));
        $files->delete(config_path('broadcasting.php'));
        $files->delete(config_path('cors.php'));
        $files->delete(config_path('mail.php'));
        $files->delete(config_path('sanctum.php'));
        $files->delete(config_path('session.php'));
        $files->delete(config_path('view.php'));

        // Remove "lang" files...
        $files->delete(lang_path('en/auth.php'));
        $files->delete(lang_path('en/pagination.php'));
        $files->delete(lang_path('en/passwords.php'));

        // Remove "public" directory...
        $files->deleteDirectory(public_path());

        // Remove "resources" directory...
        $files->deleteDirectory(base_path('resources'));

        // Remove "routes" directory...
        $files->deleteDirectory(base_path('routes'));

        // Remove frontend asset related files and directories...
        $files->delete(base_path('package.json'));
        $files->delete(base_path('webpack.mix.js'));

        // Install stubs...
        $files->makeDirectory(app_path('Console/Commands'));

        $files->copy(__DIR__.'/stubs/convert-to-cli/EventServiceProvider.stub', app_path('Providers/EventServiceProvider.php'));
        $files->copy(__DIR__.'/stubs/convert-to-cli/ExampleTest.stub', base_path('tests/Feature/ExampleTest.php'));
        $files->copy(__DIR__.'/stubs/convert-to-cli/Handler.stub', app_path('Exceptions/Handler.php'));
        $files->copy(__DIR__.'/stubs/convert-to-cli/InspireCommand.stub', app_path('Console/Commands/InspireCommand.php'));
        $files->copy(__DIR__.'/stubs/convert-to-cli/Kernel.stub', app_path('Console/Kernel.php'));
        $files->copy(__DIR__.'/stubs/convert-to-cli/app.stub', config_path('app.php'));

        // Remove Composer packages...
        $this->removeComposerPackages([
            'laravel/sanctum',
            'fruitcake/laravel-cors'
        ]);

        $this->removeComposerPackages([
            'laravel/sail',
        ], $dev = true);
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
