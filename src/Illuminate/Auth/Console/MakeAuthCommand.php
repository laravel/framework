<?php

namespace Illuminate\Auth\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Illuminate\Console\DetectsApplicationNamespace;

class MakeAuthCommand extends Command
{
    use DetectsApplicationNamespace;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:auth';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scaffold basic login and registration views and routes';

    /**
     * The controllers that need to be exported.
     *
     * @var array
     */
    protected $controllers = [
        'HomeController.stub' => 'HomeController.php',
    ];

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
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $this->createDirectories();

        $this->exportViews();

        if (! $this->option('views')) {
            $this->exportControllers();

            $routeFile = base_path('routes/web.php');
            $routes = file_get_contents(__DIR__.'/stubs/make/routes.stub');

            if (strpos(file_get_contents($routeFile), $routes) === false) {
                file_put_contents(
                    $routeFile,
                    $routes,
                    FILE_APPEND
                );
            }
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
    }

    /**
     * Export the authentication controllers.
     *
     * @return bool
     */
    protected function exportControllers()
    {
        foreach ($this->controllers as $key => $value) {
            if (file_exists(app_path('Http/Controllers/'.$value)) && ! $this->option('force')) {
                if (! $this->confirm("The [{$value}] controller already exists. Do you want to replace it?", true)) {
                    continue;
                }
            }

            file_put_contents(
                app_path('Http/Controllers/'.$value),
                $this->compileControllerStub($key)
            );
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
            if (file_exists(resource_path('views/'.$value)) && ! $this->option('force')) {
                if (! $this->confirm("The [{$value}] view already exists. Do you want to replace it?")) {
                    continue;
                }
            }

            copy(
                __DIR__.'/stubs/make/views/'.$key,
                resource_path('views/'.$value)
            );
        }
    }

    /**
     * Compiles the Controller stubs.
     *
     * @param string $file
     * @return string
     */
    protected function compileControllerStub($file)
    {
        return str_replace(
            '{{namespace}}',
            $this->getAppNamespace(),
            file_get_contents(__DIR__."/stubs/make/controllers/{$file}")
        );
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['views', null, InputOption::VALUE_OPTIONAL, 'Only scaffold the authentication views.'],

            ['force', null, InputOption::VALUE_OPTIONAL, 'Overwrite existing views by default.'],
        ];
    }
}
