<?php

namespace Illuminate\Auth\Console;

use Illuminate\Console\Command;

class MakeAuthCommand extends Command
{
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
     * THe views that need to be exported.
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
        $this->createDirectories();

        $this->exportViews();

        $this->info('Installed HomeController.');

        copy(__DIR__.'/stubs/make/controllers/HomeController.stub', app_path('Http/Controllers/HomeController.php'));

        $this->info('Updated Routes File.');

        copy(__DIR__.'/stubs/make/routes.stub', app_path('Http/routes.php'));

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
}
