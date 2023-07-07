<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Process\PhpExecutableFinder;

#[AsCommand(name: 'install:api')]
class ApiInstallCommand extends Command
{
    use InteractsWithComposerPackages;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'install:api
                    {--composer=global : Absolute path to the Composer binary which should be used to install packages}
                    {--force : Overwrite any existing API routes file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an API routes file and install Laravel Sanctum';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (file_exists($apiRoutesPath = $this->laravel->basePath('routes/api.php')) &&
            ! $this->option('force')) {
            $this->components->error('API routes file already exists.');
        } else {
            $this->components->info('Published API routes file.');

            copy(__DIR__.'/stubs/api-routes.stub', $apiRoutesPath);

            (new Filesystem)->replaceInFile(
                '// api: ',
                'api: ',
                $this->laravel->bootstrapPath('app.php'),
            );
        }

        $this->installSanctum();

        $this->components->info('API scaffolding installed. Please add the "Laravel\Sanctum\HasApiTokens" trait to your User model.');
    }

    /**
     * Install Laravel Sanctum into the application.
     *
     * @return void
     */
    protected function installSanctum()
    {
        $this->requireComposerPackages($this->option('composer'), [
            'laravel/sanctum:dev-develop'
        ]);

        $php = (new PhpExecutableFinder())->find(false) ?: 'php';

        $result = Process::run([
            $php,
            defined('ARTISAN_BINARY') ? ARTISAN_BINARY : 'artisan',
            'vendor:publish',
            '--provider',
            'Laravel\\Sanctum\\SanctumServiceProvider',
        ]);
    }
}
