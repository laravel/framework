<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'install:broadcasting')]
class BroadcastingInstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'install:broadcasting
                    {--force : Overwrite any existing broadcasting routes file}';

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
        // Install channel routes file...
        if (file_exists($broadcastingRoutesPath = $this->laravel->basePath('routes/channels.php')) &&
            ! $this->option('force')) {
            $this->components->error('Broadcasting routes file already exists.');
        } else {
            $this->components->info('Published broadcasting routes file.');

            copy(__DIR__.'/stubs/broadcasting-routes.stub', $broadcastingRoutesPath);

            $this->uncommentChannelsRoutesFile();
        }

        // Install bootstrapping...
        if (! file_exists($echoScriptPath = $this->laravel->resourcePath('js/echo.js'))) {
            copy(__DIR__.'/stubs/echo-js.stub', $echoScriptPath);
        }

        if (file_exists($bootstrapScriptPath = $this->laravel->resourcePath('js/bootstrap.js'))) {
            $bootstrapScript = file_get_contents(
                $bootstrapScriptPath
            );

            if (! str_contains($bootstrapScript, 'echo.js')) {
                file_put_contents(
                    $bootstrapScriptPath,
                    trim($bootstrapScript.PHP_EOL.file_get_contents(__DIR__.'/stubs/echo-bootstrap-js.stub')).PHP_EOL,
                );
            }
        }
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
        } elseif (str_contains($content, 'commands: __DIR__.\'/../routes/console.php\',')) {
            (new Filesystem)->replaceInFile(
                'commands: __DIR__.\'/../routes/console.php\',',
                'commands: __DIR__.\'/../routes/console.php\','.PHP_EOL.'        channels: __DIR__.\'/../routes/channels.php\',',
                $appBootstrapPath,
            );
        } else {
            $this->components->warn('Unable to automatically add channel route definition to bootstrap file. Channel route file should be registered manually.');

            return;
        }
    }
}
