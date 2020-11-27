<?php

namespace Illuminate\Foundation\Console;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Foundation\Exceptions\RegisterErrorViewPaths;
use Illuminate\Support\Facades\View;

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
                                 {--secret= : The secret phrase that may be used to bypass maintenance mode}
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
            if (is_file(storage_path('framework/down'))) {
                $this->comment('Application is already down.');

                return 0;
            }

            file_put_contents(
                storage_path('framework/down'),
                json_encode($this->getDownFilePayload(), JSON_PRETTY_PRINT)
            );

            file_put_contents(
                storage_path('framework/maintenance.php'),
                file_get_contents(__DIR__.'/stubs/maintenance-mode.stub')
            );

            $this->comment('Application is now in maintenance mode.');
        } catch (Exception $e) {
            $this->error('Failed to enter maintenance mode.');

            $this->error($e->getMessage());

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
            'redirect' => $this->redirectPath(),
            'retry' => $this->getRetryTime(),
            'secret' => $this->option('secret'),
            'status' => (int) $this->option('status', 503),
            'template' => $this->option('render') ? $this->prerenderView() : null,
        ];
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
}
