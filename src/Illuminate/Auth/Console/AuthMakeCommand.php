<?php

namespace Illuminate\Auth\Console;

use Illuminate\Console\Command;
use Illuminate\Console\DetectsApplicationNamespace;

class AuthMakeCommand extends Command
{
    use DetectsApplicationNamespace;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:auth
                    {--views : Only scaffold the authentication views}
                    {--force : Overwrite existing views by default}
                    {--no-tests : Do not generate tests}';

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
        'layouts/app.stub' => 'layouts/app.blade.php',
        'home.stub' => 'home.blade.php',
    ];

    /**
     * The tests that need to be exported.
     *
     * @var array
     */
    protected $tests = [
        'Feature/Auth/LoginTest.stub' => 'Feature/Auth/LoginTest.php',
        'Feature/Auth/RegisterTest.stub' => 'Feature/Auth/RegisterTest.php',
        'Feature/Auth/ForgotPasswordTest.stub' => 'Feature/Auth/ForgotPasswordTest.php',
        'Feature/Auth/ResetPasswordTest.stub' => 'Feature/Auth/ResetPasswordTest.php',
    ];

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->createDirectories();

        $this->exportViews();

        if (! $this->option('views')) {
            file_put_contents(
                app_path('Http/Controllers/HomeController.php'),
                $this->compileControllerStub()
            );

            file_put_contents(
                base_path('routes/web.php'),
                file_get_contents(__DIR__.'/stubs/make/routes.stub'),
                FILE_APPEND
            );
        }

        if (! $this->option('no-tests')) {
            $this->exportTests();
        }

        $this->info('Authentication scaffolding generated successfully.');
    }

    /**
     * Create the directories for the files.
     *
     * @return void
     */
    protected function createDirectories()
    {
        if (! is_dir(resource_path('views/layouts'))) {
            mkdir(resource_path('views/layouts'), 0755, true);
        }

        if (! is_dir(resource_path('views/auth/passwords'))) {
            mkdir(resource_path('views/auth/passwords'), 0755, true);
        }

        if (! $this->option('no-tests') && ! is_dir(base_path('tests/Feature/Auth'))) {
            mkdir(base_path('tests/Feature/Auth'), 0755, true);
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
            $this->exportStub(
                __DIR__.'/stubs/make/views/'.$key,
                resource_path($filename = 'views/'.$value),
                $filename
            );
        }
    }

    /**
     * Export the authentication tests.
     *
     * @return void
     */
    public function exportTests()
    {
        foreach ($this->tests as $key => $value) {
            $this->exportStub(
                __DIR__.'/stubs/make/tests/'.$key,
                base_path($filename = 'tests/'.$value),
                $filename
            );
        }
    }

    /**
     * Export a stub from the source location to the given location.
     *
     * @param  string  $source
     * @param  string  $destination
     * @param  string  $filename
     * @return void
     */
    protected function exportStub($source, $destination, $filename)
    {
        if (file_exists($destination) && ! $this->option('force')) {
            if (! $this->confirm("The [{$filename}] file already exists. Do you want to replace it?")) {
                return;
            }
        }

        copy($source, $destination);
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
}
