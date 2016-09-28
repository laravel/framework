<?php

namespace Illuminate\Auth\Console;

use Illuminate\Console\Command;
use Illuminate\Console\AppNamespaceDetectorTrait;

class MakeAuthCommand extends Command
{
    use AppNamespaceDetectorTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:auth {--views : Only scaffold the authentication views}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scaffold basic login and registration views and routes';

    /**
     * The views that need to be exported.
     *
     * @var array
     */
    protected $views = [
        'auth/login.stub' => 'auth/login.blade.php',
        'auth/register.stub' => 'auth/register.blade.php',
        'auth/passwords/email.stub' => 'auth/passwords/email.blade.php',
        'auth/passwords/reset.stub' => 'auth/passwords/reset.blade.php',
        'auth/emails/password.stub' => 'auth/emails/password.blade.php',
        'layouts/app.stub' => 'layouts/app.blade.php',
        'home.stub' => 'home.blade.php',
        'welcome.stub' => 'welcome.blade.php',
    ];

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $existFiles = $this->checkExistFiles();

        if (count($existFiles)) {
            $this->warn("\nWarning!");
            $this->warn("The following files already exist. If you continue to be overwritten on them and you will lose your old files.\n");

            foreach ($existFiles as $key => $value) {
                $this->error($value);
            }

            if ($this->confirm('Do you want to continue? [y|N]')) {
                $this->makeAuth();
            }
        } else {
            $this->makeAuth();
        }
    }

    /**
     * Creating auth files.
     *
     * @return void
     */
    protected function makeAuth()
    {
        $this->createDirectories();

        $this->exportViews();

        if (! $this->option('views')) {
            $this->info('Installed HomeController.');

            file_put_contents(
                app_path('Http/Controllers/HomeController.php'),
                $this->compileControllerStub()
            );

            $this->info('Updated Routes File.');

            file_put_contents(
                app_path('Http/routes.php'),
                file_get_contents(__DIR__.'/stubs/make/routes.stub'),
                FILE_APPEND
            );
        }

        $this->comment('Authentication scaffolding generated successfully!');
    }

    /**
     * Create the directories for the files.
     *
     * @return void
     */
    protected function createDirectories()
    {
        if (! is_dir(base_path('resources/views/layouts'))) {
            mkdir(base_path('resources/views/layouts'), 0755, true);
        }

        if (! is_dir(base_path('resources/views/auth/passwords'))) {
            mkdir(base_path('resources/views/auth/passwords'), 0755, true);
        }

        if (! is_dir(base_path('resources/views/auth/emails'))) {
            mkdir(base_path('resources/views/auth/emails'), 0755, true);
        }
    }

    /**
     * Export the authentication views.
     *
     * @return void
     */
    protected function exportViews()
    {
        foreach ($this->views as $key => $value) {
            $path = base_path('resources/views/'.$value);

            $this->line('<info>Created View:</info> '.$path);

            copy(__DIR__.'/stubs/make/views/'.$key, $path);
        }
    }

    /**
     * Compiles the HomeController stub.
     *
     * @return string
     */
    protected function compileControllerStub()
    {
        return str_replace(
            '{{namespace}}',
            $this->getAppNamespace(),
            file_get_contents(__DIR__.'/stubs/make/controllers/HomeController.stub')
        );
    }

    /**
     * Check exist the view and controller files.
     *
     * @return array
     */
    protected function checkExistFiles()
    {
        $exists = [];

        foreach ($this->views as $key => $value) {
            $file = base_path('resources/views/'.$value);

            if (file_exists($file)) {
                $exists[] = $file;
            }
        }

        $file = base_path('app/Http/Controllers/HomeController.php');

        if (file_exists($file)) {
            $exists[] = $file;
        }

        return $exists;
    }
}
