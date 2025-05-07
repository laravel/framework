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

    protected $frameworkPackages = [
        'vue' => '@laravel/echo-vue',
        'react' => '@laravel/echo-react',
    ];

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

            copy(__DIR__ . '/stubs/broadcasting-routes.stub', $broadcastingRoutesPath);
        }

        $this->uncommentChannelsRoutesFile();
        $this->enableBroadcastServiceProvider();

        if ($this->isUsingSupportedFramework()) {
            // If this is a supported framework, we will use the framework-specific Echo helpers
            $this->injectFrameworkSpecificConfiguration();
        } else {
            // Standard JavaScript implementation
            if (! file_exists($echoScriptPath = $this->laravel->resourcePath('js/echo.js'))) {
                if (! is_dir($directory = $this->laravel->resourcePath('js'))) {
                    mkdir($directory, 0755, true);
                }

                copy(__DIR__ . '/stubs/echo-js.stub', $echoScriptPath);
            }

            // Only add the bootstrap import for the standard JS implementation
            if (file_exists($bootstrapScriptPath = $this->laravel->resourcePath('js/bootstrap.js'))) {
                $bootstrapScript = file_get_contents(
                    $bootstrapScriptPath
                );

                if (! str_contains($bootstrapScript, './echo')) {
                    file_put_contents(
                        $bootstrapScriptPath,
                        trim($bootstrapScript . PHP_EOL . file_get_contents(__DIR__ . '/stubs/echo-bootstrap-js.stub')) . PHP_EOL,
                    );
                }
            }
        }

        $this->installReverb();

        $this->installNodeDependencies();
    }

    /**
     * Detect if the user is using a supported framework (React or Vue)
     *
     * @return bool
     */
    protected function isUsingSupportedFramework(): bool
    {
        return $this->appUsesReact() || $this->appUsesVue();
    }

    /**
     * Detect if the user is using React
     *
     * @return bool
     */
    protected function appUsesReact(): bool
    {
        return $this->packageDependenciesInclude('react');
    }

    /**
     * Detect if the user is using Vue
     *
     * @return bool
     */
    protected function appUsesVue(): bool
    {
        return $this->packageDependenciesInclude('vue');
    }

    /**
     * Detect if the package is installed
     *
     * @return bool
     */
    protected function packageDependenciesInclude(string $package): bool
    {
        $packageJsonPath = $this->laravel->basePath('package.json');

        if (!file_exists($packageJsonPath)) {
            return false;
        }

        $packageJson = json_decode(file_get_contents($packageJsonPath), true);

        return isset($packageJson['dependencies'][$package]) ||
            isset($packageJson['devDependencies'][$package]);
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
                'commands: __DIR__.\'/../routes/console.php\',' . PHP_EOL . '        channels: __DIR__.\'/../routes/channels.php\',',
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

        if (
            ! $filesystem->exists(app()->configPath('app.php')) ||
            ! $filesystem->exists('app/Providers/BroadcastServiceProvider.php')
        ) {
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
    protected function injectFrameworkSpecificConfiguration()
    {
        if ($this->appUsesVue()) {
            $importPath = $this->frameworkPackages['vue'];
            $filePaths = [
                $this->laravel->resourcePath('js/app.ts'),
                $this->laravel->resourcePath('js/app.js'),
            ];
        } else {
            $importPath = $this->frameworkPackages['react'];
            $filePaths = [
                $this->laravel->resourcePath('js/app.tsx'),
                $this->laravel->resourcePath('js/app.jsx'),
            ];
        }

        $filePath = array_filter($filePaths, function ($path) {
            return file_exists($path);
        })[0] ?? null;

        // Check if file exists
        if (!$filePath) {
            $this->components->warn("Could not find {$filePaths[0]}. Echo configuration not added.");
            return;
        }

        $contents = file_get_contents($filePath);

        $echoCode = <<<JS
        import { configureEcho } from '{$importPath}';

        configureEcho({
            broadcaster: 'reverb',
        });
        JS;

        preg_match_all('/^import .+;$/m', $contents, $matches);

        if (empty($matches[0])) {
            // Add the Echo configuration to the top of the file if no import statements are found
            $newContents = $echoCode . PHP_EOL . $contents;
            file_put_contents($filePath, $newContents);
        } else {
            // Add Echo configuration after the last import
            $lastImport = end($matches[0]);
            $pos = strrpos($contents, $lastImport);

            if ($pos !== false) {
                $insertPos = $pos + strlen($lastImport);
                $newContents = substr($contents, 0, $insertPos) . PHP_EOL . $echoCode . substr($contents, $insertPos);
                file_put_contents($filePath, $newContents);
            }
        }

        $this->components->info('Echo configuration added to ' . basename($filePath) . '.');
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

        $additionalPackage = '';

        if ($this->appUsesVue()) {
            $additionalPackage = $this->frameworkPackages['vue'];
        } elseif ($this->appUsesReact()) {
            $additionalPackage = $this->frameworkPackages['react'];
        }

        if (file_exists(base_path('pnpm-lock.yaml'))) {
            $commands = [
                trim('pnpm add --save-dev laravel-echo pusher-js ' . $additionalPackage),
                'pnpm run build',
            ];
        } elseif (file_exists(base_path('yarn.lock'))) {
            $commands = [
                trim('yarn add --dev laravel-echo pusher-js ' . $additionalPackage),
                'yarn run build',
            ];
        } elseif (file_exists(base_path('bun.lock')) || file_exists(base_path('bun.lockb'))) {
            $commands = [
                trim('bun add --dev laravel-echo pusher-js ' . $additionalPackage),
                'bun run build',
            ];
        } else {
            $commands = [
                trim('npm install --save-dev laravel-echo pusher-js ' . $additionalPackage),
                'npm run build',
            ];
        }

        $command = Process::command(implode(' && ', $commands))
            ->path(base_path());

        if (! windows_os()) {
            $command->tty(true);
        }

        if ($command->run()->failed()) {
            $this->components->warn("Node dependency installation failed. Please run the following commands manually: \n\n" . implode(' && ', $commands));
        } else {
            $this->components->info('Node dependencies installed successfully.');
        }
    }
}
