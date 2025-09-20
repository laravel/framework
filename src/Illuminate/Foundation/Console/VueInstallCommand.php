<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'install:vue')]
class VueInstallCommand extends Command
{
    use InteractsWithComposerPackages;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'install:vue
                    {--composer=global : Absolute path to the Composer binary which should be used to install packages}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install and configure Vue.js scaffolding in a fresh Laravel installation';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $inertiaMiddlewarePath = $this->laravel->path('Http/Middleware/HandleInertiaRequests.php');

        $inertiaConfigPath = $this->laravel->configPath('inertia.php');

        if (file_exists($inertiaMiddlewarePath) || file_exists($inertiaConfigPath)) {
            $this->components->error('Vue.js scaffolding is already configured.');
        } else {
            $this->components->info('Install and configure Vue.js scaffolding.');

            $this->installInertia();

            $this->installZiggy();

            $this->ensureDirectoriesExist([
                $this->laravel->path('Http/Middleware'),
                $this->laravel->resourcePath('js/lib'),
                $this->laravel->resourcePath('js/pages'),
                $this->laravel->resourcePath('js/types'),
            ]);

            $this->publishFiles([
                // inertia files
                __DIR__.'/stubs/vue-scaffolding/inertia/HandleInertiaRequests.stub' => $inertiaMiddlewarePath,
                __DIR__.'/stubs/vue-scaffolding/inertia/inertia.stub' => $inertiaConfigPath,

                // js files
                __DIR__.'/stubs/vue-scaffolding/js/lib/utils.stub' => $this->laravel->resourcePath('js/lib/utils.ts'),
                __DIR__.'/stubs/vue-scaffolding/js/pages/Welcome.stub' => $this->laravel->resourcePath('js/pages/Welcome.vue'),
                __DIR__.'/stubs/vue-scaffolding/js/types/globals.stub' => $this->laravel->resourcePath('js/types/globals.d.ts'),
                __DIR__.'/stubs/vue-scaffolding/js/types/index.stub' => $this->laravel->resourcePath('js/types/index.d.ts'),
                __DIR__.'/stubs/vue-scaffolding/js/types/ziggy.stub' => $this->laravel->resourcePath('js/types/ziggy.d.ts'),
                __DIR__.'/stubs/vue-scaffolding/js/app.stub' => $this->laravel->resourcePath('js/app.ts'),
                __DIR__.'/stubs/vue-scaffolding/js/ssr.stub' => $this->laravel->resourcePath('js/ssr.ts'),

                // view files
                __DIR__.'/stubs/vue-scaffolding/welcome.stub' => $this->laravel->resourcePath('views/welcome.blade.php'),

                // routes files
                __DIR__.'/stubs/vue-scaffolding/web.stub' => $this->laravel->basePath('routes/web.php'),

                // bootstrap files
                __DIR__.'/stubs/vue-scaffolding/app.stub' => $this->laravel->basePath('bootstrap/app.php'),

                // other files
                __DIR__.'/stubs/vue-scaffolding/package.stub' => $this->laravel->basePath('package.json'),
                __DIR__.'/stubs/vue-scaffolding/eslint.config.stub' => $this->laravel->basePath('eslint.config.js'),
                __DIR__.'/stubs/vue-scaffolding/tsconfig.stub' => $this->laravel->basePath('tsconfig.json'),
                __DIR__.'/stubs/vue-scaffolding/vite.config.stub' => $this->laravel->basePath('vite.config.ts'),
            ]);

            $this->deleteFiles([
                $this->laravel->resourcePath('js/app.js'),
                $this->laravel->resourcePath('js/bootstrap.js'),
                $this->laravel->basePath('vite.config.js'),
            ]);

            $this->cleanCssFile();

            $this->addDevSsrCommand();

            $this->installNodeDependencies();
        }
    }

    /**
     * Install Inertia into the application.
     *
     * @return void
     */
    protected function installInertia()
    {
        $this->components->info('Installing Inertia...');

        $this->requireComposerPackages($this->option('composer'), [
            'inertiajs/inertia-laravel:^2.0',
        ]);
    }

    /**
     * Install Ziggy into the application.
     *
     * @return void
     */
    protected function installZiggy()
    {
        $this->output->newLine();
        $this->components->info('Installing Ziggy...');

        $this->requireComposerPackages($this->option('composer'), [
            'tightenco/ziggy:^2.5',
        ]);
    }

    /**
     * Ensure that the given directories exist, creating them if necessary.
     *
     * @param  list<string>  $directories
     * @return void
     */
    protected function ensureDirectoriesExist($directories)
    {
        foreach ($directories as $directory) {
            File::ensureDirectoryExists($directory);
        }
    }

    /**
     * Publish the given files to their destination paths.
     *
     * @param  array<string, string>  $files
     * @return void
     */
    protected function publishFiles($files)
    {
        foreach ($files as $source => $destination) {
            File::copy($source, $destination);
        }
    }

    /**
     * Delete the given files if they exist.
     *
     * @param  list<string>  $files
     * @return void
     */
    protected function deleteFiles($files)
    {
        foreach ($files as $file) {
            File::delete($file);
        }
    }

    /**
     * Remove some directives from the "app.css" file.
     *
     * @return void
     */
    protected function cleanCssFile()
    {
        $filePath = $this->laravel->resourcePath('css/app.css');

        $str = "@source '../**/*.blade.php';\n@source '../**/*.js';\n";

        if (str_contains(File::get($filePath), $str)) {
            File::replaceInFile($str, '', $filePath);
        }
    }

    /**
     * Add the `dev:ssr` command to the "composer.json" file.
     *
     * @return void
     */
    protected function addDevSsrCommand()
    {
        $file = $this->laravel->basePath('composer.json');

        $content = json_decode(File::get($file), true);

        $content['scripts'] = Arr::add($content['scripts'], 'dev:ssr', [
            'npm run build:ssr',
            'Composer\\Config::disableProcessTimeout',
            'npx concurrently -c "#93c5fd,#c4b5fd,#fb7185,#fdba74" "php artisan serve" "php artisan queue:listen --tries=1" "php artisan pail --timeout=0" "php artisan inertia:start-ssr" --names=server,queue,logs,ssr --kill-others',
        ]);

        File::put(
            $file,
            json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n"
        );
    }

    /**
     * Run the `npm install` command in the base path of the application.
     *
     * @return void
     */
    protected function installNodeDependencies()
    {
        if (! $this->confirm('Would you like to install the Node dependencies?', default: true)) {
            $this->components->warn("Please run the 'npm install' command manually.");

            return;
        }

        try {
            $command = Process::timeout(120)
                ->path($this->laravel->basePath())
                ->run('npm install');

            $this->components->info('Node dependencies installed successfully.');
        } catch (ProcessTimedOutException $e) {
            $this->components->warn("Node dependency installation failed. Please run the 'npm install' command.");
        }
    }
}
