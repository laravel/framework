<?php

namespace Illuminate\Foundation\Console;

use Closure;
use Exception;
use Throwable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Console\Application as Artisan;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Console\Kernel as KernelContract;
use Symfony\Component\Debug\Exception\FatalThrowableError;

class Kernel implements KernelContract
{
    /**
     * The application implementation.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * The event dispatcher implementation.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * The Artisan application instance.
     *
     * @var \Illuminate\Console\Application
     */
    protected $artisan;

    /**
     * The Artisan commands provided by the application.
     *
     * @var array
     */
    protected $commands = [];

    /**
     * Indicates if the Closure commands have been loaded.
     *
     * @var bool
     */
    protected $commandsLoaded = false;

    /**
     * The bootstrap classes for the application.
     *
     * @var array
     */
    protected $bootstrappers = [
        'Illuminate\Foundation\Bootstrap\DetectEnvironment',
        'Illuminate\Foundation\Bootstrap\LoadConfiguration',
        'Illuminate\Foundation\Bootstrap\ConfigureLogging',
        'Illuminate\Foundation\Bootstrap\HandleExceptions',
        'Illuminate\Foundation\Bootstrap\RegisterFacades',
        'Illuminate\Foundation\Bootstrap\SetRequestForConsole',
        'Illuminate\Foundation\Bootstrap\RegisterProviders',
        'Illuminate\Foundation\Bootstrap\BootProviders',
    ];

    /**
     * Create a new console kernel instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function __construct(Application $app, Dispatcher $events)
    {
        if (! defined('ARTISAN_BINARY')) {
            define('ARTISAN_BINARY', 'artisan');
        }

        $this->app = $app;
        $this->events = $events;

        $this->app->booted(function () {
            $this->defineConsoleSchedule();
        });
    }

    /**
     * Define the application's command schedule.
     *
     * @return void
     */
    protected function defineConsoleSchedule()
    {
        $this->app->instance(
            'Illuminate\Console\Scheduling\Schedule', $schedule = new Schedule
        );

        $this->schedule($schedule);
    }

    /**
     * Run the console application.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return int
     */
    public function handle($input, $output = null)
    {
        try {
            $this->bootstrap();

            if (! $this->commandsLoaded) {
                $this->commands();

                $this->commandsLoaded = true;
            }

            return $this->getArtisan()->run($input, $output);
        } catch (Exception $e) {
            $this->reportException($e);

            $this->renderException($output, $e);

            return 1;
        } catch (Throwable $e) {
            $e = new FatalThrowableError($e);

            $this->reportException($e);

            $this->renderException($output, $e);

            return 1;
        }
    }

    /**
     * Terminate the application.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  int  $status
     * @return void
     */
    public function terminate($input, $status)
    {
        $this->app->terminate();
    }

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        //
    }

    /**
     * Register a Closure based command with the application.
     *
     * @param  string  $signature
     * @param  Closure  $callback
     * @return \Illuminate\Foundation\Console\ClosureCommand
     */
    public function command($signature, Closure $callback)
    {
        $command = new ClosureCommand($signature, $callback);

        Artisan::starting(function ($artisan) use ($command) {
            $artisan->add($command);
        });

        return $command;
    }

    /**
     * Register the given command with the console application.
     *
     * @param  \Symfony\Component\Console\Command\Command  $command
     * @return void
     */
    public function registerCommand($command)
    {
        $this->getArtisan()->add($command);
    }

    /**
     * Run an Artisan console command by name.
     *
     * @param  string  $command
     * @param  array  $parameters
     * @return int
     */
    public function call($command, array $parameters = [])
    {
        $this->bootstrap();

        if (! $this->commandsLoaded) {
            $this->commands();

            $this->commandsLoaded = true;
        }

        return $this->getArtisan()->call($command, $parameters);
    }

    /**
     * Queue the given console command.
     *
     * @param  string  $command
     * @param  array   $parameters
     * @return void
     */
    public function queue($command, array $parameters = [])
    {
        $this->app['Illuminate\Contracts\Queue\Queue']->push(
            'Illuminate\Foundation\Console\QueuedJob', func_get_args()
        );
    }

    /**
     * Get all of the commands registered with the console.
     *
     * @return array
     */
    public function all()
    {
        $this->bootstrap();

        return $this->getArtisan()->all();
    }

    /**
     * Get the output for the last run command.
     *
     * @return string
     */
    public function output()
    {
        $this->bootstrap();

        return $this->getArtisan()->output();
    }

    /**
     * Bootstrap the application for artisan commands.
     *
     * @return void
     */
    public function bootstrap()
    {
        if (! $this->app->hasBeenBootstrapped()) {
            $this->app->bootstrapWith($this->bootstrappers());
        }

        // If we are calling an arbitrary command from within the application, we'll load
        // all of the available deferred providers which will make all of the commands
        // available to an application. Otherwise the command will not be available.
        $this->app->loadDeferredProviders();
    }

    /**
     * Get the Artisan application instance.
     *
     * @return \Illuminate\Console\Application
     */
    protected function getArtisan()
    {
        if (is_null($this->artisan)) {
            return $this->artisan = (new Artisan($this->app, $this->events, $this->app->version()))
                                ->resolveCommands($this->commands);
        }

        return $this->artisan;
    }

    /**
     * Set the Artisan application instance.
     *
     * @param  \Illuminate\Console\Application  $artisan
     * @return void
     */
    public function setArtisan($artisan)
    {
        $this->artisan = $artisan;
    }

    /**
     * Get the bootstrap classes for the application.
     *
     * @return array
     */
    protected function bootstrappers()
    {
        return $this->bootstrappers;
    }

    /**
     * Report the exception to the exception handler.
     *
     * @param  \Exception  $e
     * @return void
     */
    protected function reportException(Exception $e)
    {
        $this->app['Illuminate\Contracts\Debug\ExceptionHandler']->report($e);
    }

    /**
     * Report the exception to the exception handler.
     *
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @param  \Exception  $e
     * @return void
     */
    protected function renderException($output, Exception $e)
    {
        $this->app['Illuminate\Contracts\Debug\ExceptionHandler']->renderForConsole($output, $e);
    }
}
