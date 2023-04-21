<?php

namespace Illuminate\Foundation\Console;

use Carbon\CarbonInterval;
use Closure;
use DateTimeInterface;
use Illuminate\Console\Application as Artisan;
use Illuminate\Console\Command;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel as KernelContract;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Env;
use Illuminate\Support\InteractsWithTime;
use Illuminate\Support\Str;
use ReflectionClass;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Finder\Finder;
use Throwable;

class Kernel implements KernelContract
{
    use InteractsWithTime;

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
     * The Symfony event dispatcher implementation.
     *
     * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface|null
     */
    protected $symfonyDispatcher;

    /**
     * The Artisan application instance.
     *
     * @var \Illuminate\Console\Application|null
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
     * The commands paths that have been "loaded".
     *
     * @var array
     */
    protected $loadedPaths = [];

    /**
     * All of the registered command duration handlers.
     *
     * @var array
     */
    protected $commandLifecycleDurationHandlers = [];

    /**
     * When the currently handled command started.
     *
     * @var \Illuminate\Support\Carbon|null
     */
    protected $commandStartedAt;

    /**
     * The bootstrap classes for the application.
     *
     * @var string[]
     */
    protected $bootstrappers = [
        \Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables::class,
        \Illuminate\Foundation\Bootstrap\LoadConfiguration::class,
        \Illuminate\Foundation\Bootstrap\HandleExceptions::class,
        \Illuminate\Foundation\Bootstrap\RegisterFacades::class,
        \Illuminate\Foundation\Bootstrap\SetRequestForConsole::class,
        \Illuminate\Foundation\Bootstrap\RegisterProviders::class,
        \Illuminate\Foundation\Bootstrap\BootProviders::class,
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
            if (! $this->app->runningUnitTests()) {
                $this->rerouteSymfonyCommandEvents();
            }

            $this->defineConsoleSchedule();
        });
    }

    /**
     * Re-route the Symfony command events to their Laravel counterparts.
     *
     * @internal
     *
     * @return $this
     */
    public function rerouteSymfonyCommandEvents()
    {
        if (is_null($this->symfonyDispatcher)) {
            $this->symfonyDispatcher = new EventDispatcher;

            $this->symfonyDispatcher->addListener(ConsoleEvents::COMMAND, function (ConsoleCommandEvent $event) {
                $this->events->dispatch(
                    new CommandStarting($event->getCommand()->getName(), $event->getInput(), $event->getOutput())
                );
            });

            $this->symfonyDispatcher->addListener(ConsoleEvents::TERMINATE, function (ConsoleTerminateEvent $event) {
                $this->events->dispatch(
                    new CommandFinished($event->getCommand()->getName(), $event->getInput(), $event->getOutput(), $event->getExitCode())
                );
            });
        }

        return $this;
    }

    /**
     * Define the application's command schedule.
     *
     * @return void
     */
    protected function defineConsoleSchedule()
    {
        $this->app->singleton(Schedule::class, function ($app) {
            return tap(new Schedule($this->scheduleTimezone()), function ($schedule) {
                $this->schedule($schedule->useCache($this->scheduleCache()));
            });
        });
    }

    /**
     * Get the name of the cache store that should manage scheduling mutexes.
     *
     * @return string
     */
    protected function scheduleCache()
    {
        return $this->app['config']->get('cache.schedule_store', Env::get('SCHEDULE_CACHE_DRIVER'));
    }

    /**
     * Run the console application.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface|null  $output
     * @return int
     */
    public function handle($input, $output = null)
    {
        $this->commandStartedAt = Carbon::now();

        try {
            if (in_array($input->getFirstArgument(), ['env:encrypt', 'env:decrypt'], true)) {
                $this->bootstrapWithoutBootingProviders();
            }

            $this->bootstrap();

            return $this->getArtisan()->run($input, $output);
        } catch (Throwable $e) {
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

        foreach ($this->commandLifecycleDurationHandlers as ['threshold' => $threshold, 'handler' => $handler]) {
            $end ??= Carbon::now();

            if ($this->commandStartedAt->diffInMilliseconds($end) > $threshold) {
                $handler($this->commandStartedAt, $input, $status);
            }
        }

        $this->commandStartedAt = null;
    }

    /**
     * Register a callback to be invoked when the command lifecycle duration exceeds a given amount of time.
     *
     * @param  \DateTimeInterface|\Carbon\CarbonInterval|float|int  $threshold
     * @param  callable  $handler
     * @return void
     */
    public function whenCommandLifecycleIsLongerThan($threshold, $handler)
    {
        $threshold = $threshold instanceof DateTimeInterface
            ? $this->secondsUntil($threshold) * 1000
            : $threshold;

        $threshold = $threshold instanceof CarbonInterval
            ? $threshold->totalMilliseconds
            : $threshold;

        $this->commandLifecycleDurationHandlers[] = [
            'threshold' => $threshold,
            'handler' => $handler,
        ];
    }

    /**
     * When the command being handled started.
     *
     * @return \Illuminate\Support\Carbon|null
     */
    public function commandStartedAt()
    {
        return $this->commandStartedAt;
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
     * Get the timezone that should be used by default for scheduled events.
     *
     * @return \DateTimeZone|string|null
     */
    protected function scheduleTimezone()
    {
        $config = $this->app['config'];

        return $config->get('app.schedule_timezone', $config->get('app.timezone'));
    }

    /**
     * Register the commands for the application.
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
     * @param  \Closure  $callback
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
     * Register all of the commands in the given directory.
     *
     * @param  array|string  $paths
     * @return void
     */
    protected function load($paths)
    {
        $paths = array_unique(Arr::wrap($paths));

        $paths = array_filter($paths, function ($path) {
            return is_dir($path);
        });

        if (empty($paths)) {
            return;
        }

        $this->loadedPaths = array_values(
            array_unique(array_merge($this->loadedPaths, $paths))
        );

        $namespace = $this->app->getNamespace();

        foreach ((new Finder)->in($paths)->files() as $command) {
            $command = $namespace.str_replace(
                ['/', '.php'],
                ['\\', ''],
                Str::after($command->getRealPath(), realpath(app_path()).DIRECTORY_SEPARATOR)
            );

            if (is_subclass_of($command, Command::class) &&
                ! (new ReflectionClass($command))->isAbstract()) {
                Artisan::starting(function ($artisan) use ($command) {
                    $artisan->resolve($command);
                });
            }
        }
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
     * @param  \Symfony\Component\Console\Output\OutputInterface|null  $outputBuffer
     * @return int
     *
     * @throws \Symfony\Component\Console\Exception\CommandNotFoundException
     */
    public function call($command, array $parameters = [], $outputBuffer = null)
    {
        if (in_array($command, ['env:encrypt', 'env:decrypt'], true)) {
            $this->bootstrapWithoutBootingProviders();
        }

        $this->bootstrap();

        return $this->getArtisan()->call($command, $parameters, $outputBuffer);
    }

    /**
     * Queue the given console command.
     *
     * @param  string  $command
     * @param  array  $parameters
     * @return \Illuminate\Foundation\Bus\PendingDispatch
     */
    public function queue($command, array $parameters = [])
    {
        return QueuedCommand::dispatch(func_get_args());
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

        $this->app->loadDeferredProviders();

        if (! $this->commandsLoaded) {
            $this->commands();

            if ($this->shouldDiscoverCommands() &&
                ! in_array($defaultCommandPath = $this->defaultCommandPath(), $this->loadedPaths)) {
                $this->load($defaultCommandPath);
            }

            $this->commandsLoaded = true;
        }
    }

    /**
     * Bootstrap the application without booting service providers.
     *
     * @return void
     */
    public function bootstrapWithoutBootingProviders()
    {
        $this->app->bootstrapWith(
            collect($this->bootstrappers())->reject(function ($bootstrapper) {
                return $bootstrapper === \Illuminate\Foundation\Bootstrap\BootProviders::class;
            })->all()
        );
    }

    /**
     * Get the default command path for the kernel.
     *
     * @return string
     */
    protected function defaultCommandPath()
    {
        return dirname((new ReflectionClass($this))->getFileName()).'/Commands';
    }

    /**
     * Determine if the kernel should discover commands.
     *
     * @return bool
     */
    protected function shouldDiscoverCommands()
    {
        return true;
    }

    /**
     * Get the Artisan application instance.
     *
     * @return \Illuminate\Console\Application
     */
    protected function getArtisan()
    {
        if (is_null($this->artisan)) {
            $this->artisan = (new Artisan($this->app, $this->events, $this->app->version()))
                                    ->resolveCommands($this->commands)
                                    ->setContainerCommandLoader();

            if ($this->symfonyDispatcher instanceof EventDispatcher) {
                $this->artisan->setDispatcher($this->symfonyDispatcher);
            }
        }

        return $this->artisan;
    }

    /**
     * Set the Artisan application instance.
     *
     * @param  \Illuminate\Console\Application|null  $artisan
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
     * @param  \Throwable  $e
     * @return void
     */
    protected function reportException(Throwable $e)
    {
        $this->app[ExceptionHandler::class]->report($e);
    }

    /**
     * Render the given exception.
     *
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @param  \Throwable  $e
     * @return void
     */
    protected function renderException($output, Throwable $e)
    {
        $this->app[ExceptionHandler::class]->renderForConsole($output, $e);
    }
}
