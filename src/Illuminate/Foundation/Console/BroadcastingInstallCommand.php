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
        if (file_exists($broadcastingRoutesPath = $this->laravel->basePath('routes/channels.php')) &&
            ! $this->option('force')) {
            $this->components->error('Broadcasting routes file already exists.');
        } else {
            $this->components->info('Published broadcasting routes file.');

            copy(__DIR__.'/stubs/broadcasting-routes.stub', $broadcastingRoutesPath);

            (new Filesystem)->replaceInFile(
                '// channels: ',
                'channels: ',
                $this->laravel->bootstrapPath('app.php'),
            );
        }

        (new Filesystem)->replaceInFile(
            "// import './echo';",
            "import './echo';",
            $this->laravel->resourcePath('js/bootstrap.js'),
        );
    }
}
