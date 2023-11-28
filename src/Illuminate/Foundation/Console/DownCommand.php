<?php

namespace Illuminate\Foundation\Console;

use App\Http\Middleware\PreventRequestsDuringMaintenance;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Foundation\Events\MaintenanceModeEnabled;
use Illuminate\Foundation\Exceptions\RegisterErrorViewPaths;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;
use Illuminate\Support\Str;

#[AsCommand(name: 'down')]
class DownCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'down {--redirect= : The path that users should be redirected to}
                                 {--render= : The view that should be prerendered for display during maintenance mode}
                                 {--retry= : The number of seconds after which the request may be retried}
                                 {--refresh= : The number of seconds after which the browser may refresh}
                                 {--secret= : The secret phrase that may be used to bypass maintenance mode}
                                 {--generate-secret : Generate a random secret phrase that may be used to bypass maintenance mode}
                                 {--status=503 : The status code that should be used when returning the maintenance mode response}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Put the application into maintenance / demo mode';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            if ($this->laravel->maintenanceMode()->active()) {
                $this->components->info('Application is already down.');

                return 0;
            }

            $downFilePayload = $this->getDownFilePayload();

            $this->laravel->maintenanceMode()->activate($downFilePayload);

            file_put_contents(
                storage_path('framework/maintenance.php'),
                file_get_contents(__DIR__.'/stubs/maintenance-mode.stub')
            );

            $this->laravel->get('events')->dispatch(new MaintenanceModeEnabled());

            $this->components->info('Application is now in maintenance mode.');

            if ($downFilePayload['secret'] !== null) {
                $this->components->info("You may bypass the application's maintenace mode by acessing the following URL [" . config('app.url') . "/{$downFilePayload['secret']}].");
            }
        } catch (Exception $e) {
            $this->components->error(sprintf(
                'Failed to enter maintenance mode: %s.',
                $e->getMessage(),
            ));

            return 1;
        }
    }

    /**
     * Get the payload to be placed in the "down" file.
     *
     * @return array
     */
    protected function getDownFilePayload()
    {
        return [
            'except' => $this->excludedPaths(),
            'redirect' => $this->redirectPath(),
            'retry' => $this->getRetryTime(),
            'refresh' => $this->option('refresh'),
            'secret' => $this->getSecretPhrase(),
            'status' => (int) $this->option('status', 503),
            'template' => $this->option('render') ? $this->prerenderView() : null,
        ];
    }

    /**
     * Get the paths that should be excluded from maintenance mode.
     *
     * @return array
     */
    protected function excludedPaths()
    {
        try {
            return $this->laravel->make(PreventRequestsDuringMaintenance::class)->getExcludedPaths();
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Get the path that users should be redirected to.
     *
     * @return string
     */
    protected function redirectPath()
    {
        if ($this->option('redirect') && $this->option('redirect') !== '/') {
            return '/'.trim($this->option('redirect'), '/');
        }

        return $this->option('redirect');
    }

    /**
     * Prerender the specified view so that it can be rendered even before loading Composer.
     *
     * @return string
     */
    protected function prerenderView()
    {
        (new RegisterErrorViewPaths)();

        return view($this->option('render'), [
            'retryAfter' => $this->option('retry'),
        ])->render();
    }

    /**
     * Get the number of seconds the client should wait before retrying their request.
     *
     * @return int|null
     */
    protected function getRetryTime()
    {
        $retry = $this->option('retry');

        return is_numeric($retry) && $retry > 0 ? (int) $retry : null;
    }

    /**
     * Get the secret phrase that may be used to bypass maintenance mode.
     *
     * @return string|null
     */
    protected function getSecretPhrase()
    {
        if ($this->option('secret') !== null) {
            return $this->option('secret');
        }

        if ($this->option('generate-secret')) {
            return (string) Str::uuid();
        }

        return null;
    }
}
