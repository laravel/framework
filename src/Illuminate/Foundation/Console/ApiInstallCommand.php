<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Console\Attribute\AsCommand;

use function Illuminate\Support\php_binary;

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
                    {--force : Overwrite any existing API routes file}
                    {--passport : Install Laravel Passport instead of Laravel Sanctum}
                    {--without-migration-prompt : Do not prompt to run pending migrations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an API routes file and install Laravel Sanctum or Laravel Passport';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if ($this->option('passport')) {
            $this->installPassport();
        } else {
            $this->installSanctum();
        }

        if (file_exists($apiRoutesPath = $this->laravel->basePath('routes/api.php')) &&
            ! $this->option('force')) {
            $this->components->error('API routes file already exists.');
        } else {
            $this->components->info('Published API routes file.');

            copy(__DIR__.'/stubs/api-routes.stub', $apiRoutesPath);

            if ($this->option('passport')) {
                (new Filesystem)->replaceInFile(
                    'auth:sanctum',
                    'auth:api',
                    $apiRoutesPath,
                );
            }

            $this->uncommentApiRoutesFile();
        }

        if ($this->option('passport')) {
            Process::run(array_filter([
                php_binary(),
                defined('ARTISAN_BINARY') ? ARTISAN_BINARY : 'artisan',
                'passport:install',
                $this->confirm('Would you like to use UUIDs for all client IDs?') ? '--uuids' : null,
            ]));

            $this->components->info('API scaffolding installed. Please add the [Laravel\Passport\HasApiTokens] trait to your User model.');

            if ($this->confirm('Would you like to add the [Laravel\Passport\HasApiTokens] trait to your User model now?', true)) {
                if (class_exists('App\\Models\\User')) {
                    $this->addTraitToModel('Laravel\Passport\HasApiTokens', 'App\\Models\\User');
                } else {
                    $this->components->warn('The [App\\Models\\User] model does not exist. Please manually add the trait to your User model if you\'ve moved or renamed it.');
                }
            }

        } else {
            if (! $this->option('without-migration-prompt')) {
                if ($this->confirm('One new database migration has been published. Would you like to run all pending database migrations?', true)) {
                    $this->call('migrate');
                }
            }

            $this->components->info('API scaffolding installed. Please add the [Laravel\Sanctum\HasApiTokens] trait to your User model.');

            if ($this->confirm('Would you like to add the [Laravel\\Sanctum\\HasApiTokens] trait to your User model now?', true)) {
                if (class_exists('App\\Models\\User')) {
                    $this->addTraitToModel('Laravel\\Sanctum\\HasApiTokens', 'App\\Models\\User');
                } else {
                    $this->components->warn('The [App\\Models\\User] model does not exist. Please manually add the trait to your User model if you\'ve moved or renamed it.');
                }
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Uncomment the API routes file in the application bootstrap file.
     *
     * @return void
     */
    protected function uncommentApiRoutesFile()
    {
        $appBootstrapPath = $this->laravel->bootstrapPath('app.php');
        $content = file_get_contents($appBootstrapPath);

        if (str_contains($content, '// api: ')) {
            (new Filesystem)->replaceInFile(
                '// api: ',
                'api: ',
                $appBootstrapPath,
            );
        } elseif (str_contains($content, 'web: __DIR__.\'/../routes/web.php\',')) {
            (new Filesystem)->replaceInFile(
                'web: __DIR__.\'/../routes/web.php\',',
                'web: __DIR__.\'/../routes/web.php\','.PHP_EOL.'        api: __DIR__.\'/../routes/api.php\',',
                $appBootstrapPath,
            );
        } else {
            $this->components->warn('Unable to automatically add API route definition to bootstrap file. API route file should be registered manually.');

            return;
        }
    }

    /**
     * Install Laravel Sanctum into the application.
     *
     * @return void
     */
    protected function installSanctum()
    {
        $this->requireComposerPackages($this->option('composer'), [
            'laravel/sanctum:^4.0',
        ]);

        $migrationPublished = (new Collection(scandir($this->laravel->databasePath('migrations'))))->contains(function ($migration) {
            return preg_match('/\d{4}_\d{2}_\d{2}_\d{6}_create_personal_access_tokens_table.php/', $migration);
        });

        if (! $migrationPublished) {
            Process::run([
                php_binary(),
                defined('ARTISAN_BINARY') ? ARTISAN_BINARY : 'artisan',
                'vendor:publish',
                '--provider',
                'Laravel\\Sanctum\\SanctumServiceProvider',
            ]);
        }
    }

    /**
     * Install Laravel Passport into the application.
     *
     * @return void
     */
    protected function installPassport()
    {
        $this->requireComposerPackages($this->option('composer'), [
            'laravel/passport:^12.0',
        ]);
    }

    /**
    * Attempt to add the given trait to the specified model.
    *
    * @return void
    */
    protected function addTraitToModel(string $trait, string $model)
    {
        $modelPath = $this->laravel->basePath(str_replace('\\', '/', $model) . '.php');

        if (! file_exists($modelPath)) {
            $this->components->error("Model not found at {$modelPath}.");
            return;
        }

        $content = file_get_contents($modelPath);
        $traitBasename = class_basename($trait);
        $sanctumTrait = 'Laravel\\Sanctum\\HasApiTokens';
        $passportTrait = 'Laravel\\Passport\\HasApiTokens';

        // Detect existing traits and warn
        if (str_contains($content, "use $sanctumTrait;")) {
            $this->warn("Sanctum is already installed in your [$model] model. Please manually switch to Passport if needed.");
            return;
        }

        if (str_contains($content, "use $passportTrait;")) {
            $this->warn("Passport is already installed in your [$model] model. Please manually switch to Sanctum if needed.");
            return;
        }

        // Confirm with the user before making changes
        if (! $this->components->confirm(
            "Would you like to add the [$trait] trait to your [$model] model now?",
            true
        )) {
            $this->components->info("No changes were made to your [$model] model.");
            return;
        }

        $modified = false;

        // Add the top-level `use` statement if missing
        $isTopLevelImported = str_contains($content, "use $trait;");

        if (! $isTopLevelImported) {
            $content = preg_replace(
                '/^(namespace\s+[\w\\\\]+;\s*(?:\/\/.*\n)*)((?:use\s+[\w\\\\]+;\n)*)/m',
                '$1$2use ' . $trait . ";\n",
                $content,
                1,
                $count
            );
            if ($count > 0) {
                $modified = true;
            }
        }

        // Add the class-level trait if missing
        $isClassLevelUsed = preg_match('/use\s+([A-Za-z,\\\\\s]+);/', $content, $matches) &&
            str_contains($matches[1], $traitBasename);

        if (! $isClassLevelUsed) {
            if (preg_match('/class\s+\w+\s+extends\s+\w+[A-Za-z\\\\]*\s*\{/', $content, $matches, PREG_OFFSET_CAPTURE)) {
                $insertPosition = $matches[0][1] + strlen($matches[0][0]);

                if (preg_match('/use\s+(.*?);/s', $content, $useMatches, PREG_OFFSET_CAPTURE, $insertPosition)) {
                    $traits = array_map('trim', explode(',', $useMatches[1][0]));

                    if (!in_array($traitBasename, $traits, true)) {
                        $traits[] = $traitBasename;
                        $content = substr_replace(
                            $content,
                            'use ' . implode(', ', $traits) . ';',
                            $useMatches[0][1],
                            strlen($useMatches[0][0])
                        );
                        $modified = true;
                    }
                } else {
                    $content = substr_replace(
                        $content,
                        "\n    use $traitBasename;",
                        $insertPosition,
                        0
                    );
                    $modified = true;
                }
            }
        }

        // Save changes if modified
        if ($modified) {
            file_put_contents($modelPath, $content);
            $this->components->info("The [$trait] trait has been added to your [$model] model.");
        } else {
            $this->components->info("No changes were made to your [$model] model.");
        }
    }
}
