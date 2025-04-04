<?php

namespace Illuminate\Foundation\Console;

use Composer\InstalledVersions;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Console\Attribute\AsCommand;

use function Illuminate\Support\artisan_binary;
use function Illuminate\Support\php_binary;
use function Laravel\Prompts\confirm;

#[AsCommand(name: 'install:broadcasting')]
class BroadcastingInstallCommand extends Command
{
    use InteractsWithComposerPackages;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'install:broadcasting
                    {--composer=global : Absolute path to the Composer binary which should be used to install packages}
                    {--force : Overwrite any existing broadcasting routes file}
                    {--without-reverb : Do not prompt to install Laravel Reverb}
                    {--without-node : Do not prompt to install Node dependencies}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a broadcasting channel routes file';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->call('config:publish', ['name' => 'broadcasting']);

        // Install channel routes file...
        if (! file_exists($broadcastingRoutesPath = $this->laravel->basePath('routes/channels.php')) || $this->option('force')) {
            $this->components->info("Published 'channels' route file.");

            copy(__DIR__.'/stubs/broadcasting-routes.stub', $broadcastingRoutesPath);
        }

        $this->uncommentChannelsRoutesFile();
        $this->enableBroadcastServiceProvider();

        // We have a specific echo version for React and Vue with Typescript,
        // so check if this app contains React or Vue with Typescript
        if ($this->appContainsReactWithTypescript() || $this->appContainsVueWithTypescript()) {
            // If this is a React/Vue app with typescript, inject the Echo configuration in the app.tsx or app.ts file
            $this->injectEchoConfigurationInApp();
        } else {
            // Standard JavaScript implementation
            if (! file_exists($echoScriptPath = $this->laravel->resourcePath('js/echo.js'))) {
                if (! is_dir($directory = $this->laravel->resourcePath('js'))) {
                    mkdir($directory, 0755, true);
                }

                copy(__DIR__.'/stubs/echo-js.stub', $echoScriptPath);
            }

            // Only add the bootstrap import for the standard JS implementation
            if (file_exists($bootstrapScriptPath = $this->laravel->resourcePath('js/bootstrap.js'))) {
                $bootstrapScript = file_get_contents(
                    $bootstrapScriptPath
                );

                if (! str_contains($bootstrapScript, './echo')) {
                    file_put_contents(
                        $bootstrapScriptPath,
                        trim($bootstrapScript.PHP_EOL.file_get_contents(__DIR__.'/stubs/echo-bootstrap-js.stub')).PHP_EOL,
                    );
                }
            }
        }

        $this->installReverb();

        $this->installNodeDependencies();
    }

    /**
     * Detect if the user is using React with TypeScript
     *
     * @return bool
     */
    protected function appContainsReactWithTypescript(): bool
    {
        $packageJsonPath = $this->laravel->basePath('package.json');
        if (!file_exists($packageJsonPath)) {
            return false;
        }
        $packageJson = json_decode(file_get_contents($packageJsonPath), true);
        return isset($packageJson['dependencies']['react']) && 
               isset($packageJson['dependencies']['typescript']);
    }

    /**
     * Detect if the user is using Vue with TypeScript
     *
     * @return bool
     */
    protected function appContainsVueWithTypescript(): bool
    {
        $packageJsonPath = $this->laravel->basePath('package.json');
        if (!file_exists($packageJsonPath)) {
            return false;
        }
        $packageJson = json_decode(file_get_contents($packageJsonPath), true);
        return isset($packageJson['dependencies']['vue']) && 
               isset($packageJson['dependencies']['typescript']);
    }

    /**
     * Uncomment the "channels" routes file in the application bootstrap file.
     *
     * @return void
     */
    protected function uncommentChannelsRoutesFile()
    {
        $appBootstrapPath = $this->laravel->bootstrapPath('app.php');

        $content = file_get_contents($appBootstrapPath);

        if (str_contains($content, '// channels: ')) {
            (new Filesystem)->replaceInFile(
                '// channels: ',
                'channels: ',
                $appBootstrapPath,
            );
        } elseif (str_contains($content, 'channels: ')) {
            return;
        } elseif (str_contains($content, 'commands: __DIR__.\'/../routes/console.php\',')) {
            (new Filesystem)->replaceInFile(
                'commands: __DIR__.\'/../routes/console.php\',',
                'commands: __DIR__.\'/../routes/console.php\','.PHP_EOL.'        channels: __DIR__.\'/../routes/channels.php\',',
                $appBootstrapPath,
            );
        }
    }

    /**
     * Uncomment the "BroadcastServiceProvider" in the application configuration.
     *
     * @return void
     */
    protected function enableBroadcastServiceProvider()
    {
        $filesystem = new Filesystem;

        if (! $filesystem->exists(app()->configPath('app.php')) ||
            ! $filesystem->exists('app/Providers/BroadcastServiceProvider.php')) {
            return;
        }

        $config = $filesystem->get(app()->configPath('app.php'));

        if (str_contains($config, '// App\Providers\BroadcastServiceProvider::class')) {
            $filesystem->replaceInFile(
                '// App\Providers\BroadcastServiceProvider::class',
                'App\Providers\BroadcastServiceProvider::class',
                app()->configPath('app.php'),
            );
        }
    }

    /**
     * Inject Echo configuration into the application's main file.
     *
     * @return void
     */
    protected function injectEchoConfigurationInApp()
    {
        // Detect which stack we are using and set appropriate configuration
        if ($this->appContainsVueWithTypescript()) {
            $filePath = resource_path('js/app.ts');
            $importPath = 'laravel-echo/vue';
            $fileExtension = 'ts';
        } else {
            // Default to React
            $filePath = resource_path('js/app.tsx');
            $importPath = 'laravel-echo/react';
            $fileExtension = 'tsx';
        }
        
        // Check if file exists
        if (!file_exists($filePath)) {
            $this->components->warn("Could not find {$filePath}. Echo configuration not added.");
            return;
        }
        
        $contents = file_get_contents($filePath);

        // Prepare Echo configuration code
        $echoCode = <<<JS
        import { configureEcho } from '{$importPath}';

        configureEcho({
            broadcaster: 'reverb',
            key: import.meta.env.VITE_REVERB_APP_KEY,
            wsHost: import.meta.env.VITE_REVERB_HOST,
            wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
            wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
            forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
            enabledTransports: ['ws', 'wss'],
        });
        JS;

        // Find all imports
        preg_match_all('/^import .+;$/m', $contents, $matches);

        // Add Echo configuration after the last import
        if (!empty($matches[0])) {
            $lastImport = end($matches[0]);
            $pos = strrpos($contents, $lastImport);
            if ($pos !== false) {
                $insertPos = $pos + strlen($lastImport);
                $newContents = substr($contents, 0, $insertPos) . "\n" . $echoCode . substr($contents, $insertPos);
                file_put_contents($filePath, $newContents);
                $this->components->info("Echo configuration added to app.{$fileExtension}.");
            }
        } else {
            // Add the Echo configuration to the top of the file if no import statements are found
            $newContents = $echoCode . "\n" . $contents;
            file_put_contents($filePath, $newContents);
            $this->components->info("Echo configuration added to the top of app.{$fileExtension}.");
        }
    }
    

    /**
     * Install Laravel Reverb into the application if desired.
     *
     * @return void
     */
    protected function installReverb()
    {
        if ($this->option('without-reverb') || InstalledVersions::isInstalled('laravel/reverb')) {
            return;
        }

        $install = confirm('Would you like to install Laravel Reverb?', default: true);

        if (! $install) {
            return;
        }

        $this->requireComposerPackages($this->option('composer'), [
            'laravel/reverb:^1.0',
        ]);

        Process::run([
            php_binary(),
            artisan_binary(),
            'reverb:install',
        ]);

        $this->components->info('Reverb installed successfully.');
    }

    /**
     * Install and build Node dependencies.
     *
     * @return void
     */
    protected function installNodeDependencies()
    {
        if ($this->option('without-node') || ! confirm('Would you like to install and build the Node dependencies required for broadcasting?', default: true)) {
            return;
        }

        $this->components->info('Installing and building Node dependencies.');

        if (file_exists(base_path('pnpm-lock.yaml'))) {
            $commands = [
                'pnpm add --save-dev laravel-echo pusher-js',
                'pnpm run build',
            ];
        } elseif (file_exists(base_path('yarn.lock'))) {
            $commands = [
                'yarn add --dev laravel-echo pusher-js',
                'yarn run build',
            ];
        } elseif (file_exists(base_path('bun.lock')) || file_exists(base_path('bun.lockb'))) {
            $commands = [
                'bun add --dev laravel-echo pusher-js',
                'bun run build',
            ];
        } else {
            $commands = [
                'npm install --save-dev laravel-echo pusher-js',
                'npm run build',
            ];
        }

        $command = Process::command(implode(' && ', $commands))
            ->path(base_path());

        if (! windows_os()) {
            $command->tty(true);
        }

        if ($command->run()->failed()) {
            $this->components->warn("Node dependency installation failed. Please run the following commands manually: \n\n".implode(' && ', $commands));
        } else {
            $this->components->info('Node dependencies installed successfully.');
        }
    }
}
