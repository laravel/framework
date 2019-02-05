<?php

namespace Illuminate\Routing\Console;

use Illuminate\Console\Command;

class GenerateControllersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:controller';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate controllers from the routes list';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $allRoutes = optional(
            optional(
                app('router')->getRoutes()
            )
        )->getActionList();

        // No route are found
        if (count($allRoutes) === 0) {
            $this->warn('No Routes are found');
            return;
        }

        $controllers = array_keys(
            $allRoutes
        );


        $cachedControllers = [];

        // Loop over all actions
        foreach ($controllers as $controller) {
            $controller = explode('@', $controller);
            // Only parse Controllers that are not parsed before
            if (! in_array($controller[0], $cachedControllers)) {
                $cachedControllers [] = $controller[0];

                if(! class_exists($controller[0])) {
                    \Illuminate\Support\Facades\Artisan::call('make:controller', [
                        'name' => $controller[0]
                    ]);

                    $this->info("{$controller[0]} Controller has been created.");
                }
            }
        }
    }
}
