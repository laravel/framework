<?php

namespace Illuminate\Foundation\Providers;

use Illuminate\Auth\Console\ClearResetsCommand;
use Illuminate\Cache\Console\CacheTableCommand;
use Illuminate\Cache\Console\ClearCommand as CacheClearCommand;
use Illuminate\Cache\Console\ForgetCommand as CacheForgetCommand;
use Illuminate\Cache\Console\PruneStaleTagsCommand;
use Illuminate\Concurrency\Console\InvokeSerializedClosureCommand;
use Illuminate\Console\Scheduling\ScheduleClearCacheCommand;
use Illuminate\Console\Scheduling\ScheduleFinishCommand;
use Illuminate\Console\Scheduling\ScheduleInterruptCommand;
use Illuminate\Console\Scheduling\ScheduleListCommand;
use Illuminate\Console\Scheduling\ScheduleRunCommand;
use Illuminate\Console\Scheduling\ScheduleTestCommand;
use Illuminate\Console\Scheduling\ScheduleWorkCommand;
use Illuminate\Console\Signals;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Database\Console\DbCommand;
use Illuminate\Database\Console\DumpCommand;
use Illuminate\Database\Console\Factories\FactoryMakeCommand;
use Illuminate\Database\Console\MonitorCommand as DatabaseMonitorCommand;
use Illuminate\Database\Console\PruneCommand;
use Illuminate\Database\Console\Seeds\SeedCommand;
use Illuminate\Database\Console\Seeds\SeederMakeCommand;
use Illuminate\Database\Console\ShowCommand;
use Illuminate\Database\Console\ShowModelCommand;
use Illuminate\Database\Console\TableCommand as DatabaseTableCommand;
use Illuminate\Database\Console\WipeCommand;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Foundation\Console\ApiInstallCommand;
use Illuminate\Foundation\Console\BroadcastingInstallCommand;
use Illuminate\Foundation\Console\CastMakeCommand;
use Illuminate\Foundation\Console\ChannelListCommand;
use Illuminate\Foundation\Console\ChannelMakeCommand;
use Illuminate\Foundation\Console\ClassMakeCommand;
use Illuminate\Foundation\Console\ClearCompiledCommand;
use Illuminate\Foundation\Console\ComponentMakeCommand;
use Illuminate\Foundation\Console\ConfigCacheCommand;
use Illuminate\Foundation\Console\ConfigClearCommand;
use Illuminate\Foundation\Console\ConfigPublishCommand;
use Illuminate\Foundation\Console\ConfigShowCommand;
use Illuminate\Foundation\Console\ConsoleMakeCommand;
use Illuminate\Foundation\Console\DocsCommand;
use Illuminate\Foundation\Console\DownCommand;
use Illuminate\Foundation\Console\EnumMakeCommand;
use Illuminate\Foundation\Console\EnvironmentCommand;
use Illuminate\Foundation\Console\EnvironmentDecryptCommand;
use Illuminate\Foundation\Console\EnvironmentEncryptCommand;
use Illuminate\Foundation\Console\EventCacheCommand;
use Illuminate\Foundation\Console\EventClearCommand;
use Illuminate\Foundation\Console\EventGenerateCommand;
use Illuminate\Foundation\Console\EventListCommand;
use Illuminate\Foundation\Console\EventMakeCommand;
use Illuminate\Foundation\Console\ExceptionMakeCommand;
use Illuminate\Foundation\Console\InterfaceMakeCommand;
use Illuminate\Foundation\Console\JobMakeCommand;
use Illuminate\Foundation\Console\JobMiddlewareMakeCommand;
use Illuminate\Foundation\Console\KeyGenerateCommand;
use Illuminate\Foundation\Console\LangPublishCommand;
use Illuminate\Foundation\Console\ListenerMakeCommand;
use Illuminate\Foundation\Console\MailMakeCommand;
use Illuminate\Foundation\Console\ModelMakeCommand;
use Illuminate\Foundation\Console\NotificationMakeCommand;
use Illuminate\Foundation\Console\ObserverMakeCommand;
use Illuminate\Foundation\Console\OptimizeClearCommand;
use Illuminate\Foundation\Console\OptimizeCommand;
use Illuminate\Foundation\Console\PackageDiscoverCommand;
use Illuminate\Foundation\Console\PolicyMakeCommand;
use Illuminate\Foundation\Console\ProviderMakeCommand;
use Illuminate\Foundation\Console\RequestMakeCommand;
use Illuminate\Foundation\Console\ResourceMakeCommand;
use Illuminate\Foundation\Console\RouteCacheCommand;
use Illuminate\Foundation\Console\RouteClearCommand;
use Illuminate\Foundation\Console\RouteListCommand;
use Illuminate\Foundation\Console\RuleMakeCommand;
use Illuminate\Foundation\Console\ScopeMakeCommand;
use Illuminate\Foundation\Console\ServeCommand;
use Illuminate\Foundation\Console\StorageLinkCommand;
use Illuminate\Foundation\Console\StorageUnlinkCommand;
use Illuminate\Foundation\Console\StubPublishCommand;
use Illuminate\Foundation\Console\TestMakeCommand;
use Illuminate\Foundation\Console\TraitMakeCommand;
use Illuminate\Foundation\Console\UpCommand;
use Illuminate\Foundation\Console\VendorPublishCommand;
use Illuminate\Foundation\Console\ViewCacheCommand;
use Illuminate\Foundation\Console\ViewClearCommand;
use Illuminate\Foundation\Console\ViewMakeCommand;
use Illuminate\Notifications\Console\NotificationTableCommand;
use Illuminate\Queue\Console\BatchesTableCommand;
use Illuminate\Queue\Console\ClearCommand as QueueClearCommand;
use Illuminate\Queue\Console\FailedTableCommand;
use Illuminate\Queue\Console\FlushFailedCommand as FlushFailedQueueCommand;
use Illuminate\Queue\Console\ForgetFailedCommand as ForgetFailedQueueCommand;
use Illuminate\Queue\Console\ListenCommand as QueueListenCommand;
use Illuminate\Queue\Console\ListFailedCommand as ListFailedQueueCommand;
use Illuminate\Queue\Console\MonitorCommand as QueueMonitorCommand;
use Illuminate\Queue\Console\PruneBatchesCommand as QueuePruneBatchesCommand;
use Illuminate\Queue\Console\PruneFailedJobsCommand as QueuePruneFailedJobsCommand;
use Illuminate\Queue\Console\RestartCommand as QueueRestartCommand;
use Illuminate\Queue\Console\RetryBatchCommand as QueueRetryBatchCommand;
use Illuminate\Queue\Console\RetryCommand as QueueRetryCommand;
use Illuminate\Queue\Console\TableCommand;
use Illuminate\Queue\Console\WorkCommand as QueueWorkCommand;
use Illuminate\Routing\Console\ControllerMakeCommand;
use Illuminate\Routing\Console\MiddlewareMakeCommand;
use Illuminate\Session\Console\SessionTableCommand;
use Illuminate\Support\ServiceProvider;

class ArtisanServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $registerCommandsWithDependencies = [
        AboutCommand::class => ['composer'],
        CacheClearCommand::class => ['cache', 'files'],
        CacheForgetCommand::class => ['cache'],
        CacheTableCommand::class => ['files'],
        CastMakeCommand::class => ['files'],
        ChannelMakeCommand::class => ['files'],
        ClassMakeCommand::class => ['files'],
        ComponentMakeCommand::class => ['files'],
        ConfigCacheCommand::class => ['files'],
        ConfigClearCommand::class => ['files'],
        ConfigPublishCommand::class => [],
        ConsoleMakeCommand::class => ['files'],
        ControllerMakeCommand::class => ['files'],
        EnumMakeCommand::class => ['files'],
        EventMakeCommand::class => ['files'],
        ExceptionMakeCommand::class => ['files'],
        FactoryMakeCommand::class => ['files'],
        EventClearCommand::class => ['files'],
        InterfaceMakeCommand::class => ['files'],
        JobMakeCommand::class => ['files'],
        JobMiddlewareMakeCommand::class => ['files'],
        ListenerMakeCommand::class => ['files'],
        MailMakeCommand::class => ['files'],
        MiddlewareMakeCommand::class => ['files'],
        ModelMakeCommand::class => ['files'],
        NotificationMakeCommand::class => ['files'],
        NotificationTableCommand::class => ['files'],
        ObserverMakeCommand::class => ['files'],
        PolicyMakeCommand::class => ['files'],
        ProviderMakeCommand::class => ['files'],
        QueueListenCommand::class => ['queue.listener'],
        QueueMonitorCommand::class => ['queue', 'events'],
        QueuePruneBatchesCommand::class => [],
        QueuePruneFailedJobsCommand::class => [],
        QueueRestartCommand::class => ['cache.store'],
        QueueWorkCommand::class => ['queue.worker', 'cache.store'],
        FailedTableCommand::class => ['files'],
        TableCommand::class => ['files'],
        BatchesTableCommand::class => ['files'],
        RequestMakeCommand::class => ['files'],
        ResourceMakeCommand::class => ['files'],
        RuleMakeCommand::class => ['files'],
        ScopeMakeCommand::class => ['files'],
        SeederMakeCommand::class => ['files'],
        SessionTableCommand::class => ['files'],
        RouteCacheCommand::class => ['files'],
        RouteClearCommand::class => ['files'],
        RouteListCommand::class => ['router'],
        SeedCommand::class => ['db'],
        TestMakeCommand::class => ['files'],
        TraitMakeCommand::class => ['files'],
        VendorPublishCommand::class => ['files'],
        ViewClearCommand::class => ['files'],
    ];

    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $commands = [
        'ClearCompiled' => ClearCompiledCommand::class,
        'ClearResets' => ClearResetsCommand::class,
        'ConfigShow' => ConfigShowCommand::class,
        'Db' => DbCommand::class,
        'DbMonitor' => DatabaseMonitorCommand::class,
        'DbPrune' => PruneCommand::class,
        'DbShow' => ShowCommand::class,
        'DbTable' => DatabaseTableCommand::class,
        'DbWipe' => WipeCommand::class,
        'Down' => DownCommand::class,
        'Environment' => EnvironmentCommand::class,
        'EnvironmentDecrypt' => EnvironmentDecryptCommand::class,
        'EnvironmentEncrypt' => EnvironmentEncryptCommand::class,
        'EventCache' => EventCacheCommand::class,
        'EventList' => EventListCommand::class,
        'InvokeSerializedClosure' => InvokeSerializedClosureCommand::class,
        'KeyGenerate' => KeyGenerateCommand::class,
        'Optimize' => OptimizeCommand::class,
        'OptimizeClear' => OptimizeClearCommand::class,
        'PackageDiscover' => PackageDiscoverCommand::class,
        'PruneStaleTagsCommand' => PruneStaleTagsCommand::class,
        'QueueClear' => QueueClearCommand::class,
        'QueueFailed' => ListFailedQueueCommand::class,
        'QueueFlush' => FlushFailedQueueCommand::class,
        'QueueForget' => ForgetFailedQueueCommand::class,
        'QueueRetry' => QueueRetryCommand::class,
        'QueueRetryBatch' => QueueRetryBatchCommand::class,
        'SchemaDump' => DumpCommand::class,
        'ScheduleFinish' => ScheduleFinishCommand::class,
        'ScheduleList' => ScheduleListCommand::class,
        'ScheduleRun' => ScheduleRunCommand::class,
        'ScheduleClearCache' => ScheduleClearCacheCommand::class,
        'ScheduleTest' => ScheduleTestCommand::class,
        'ScheduleWork' => ScheduleWorkCommand::class,
        'ScheduleInterrupt' => ScheduleInterruptCommand::class,
        'ShowModel' => ShowModelCommand::class,
        'StorageLink' => StorageLinkCommand::class,
        'StorageUnlink' => StorageUnlinkCommand::class,
        'Up' => UpCommand::class,
        'ViewCache' => ViewCacheCommand::class,
        'ViewClear' => ViewClearCommand::class,
    ];

    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $devCommands = [
        'ApiInstall' => ApiInstallCommand::class,
        'BroadcastingInstall' => BroadcastingInstallCommand::class,
        'ChannelList' => ChannelListCommand::class,
        'Docs' => DocsCommand::class,
        'EventGenerate' => EventGenerateCommand::class,
        'LangPublish' => LangPublishCommand::class,
        'Serve' => ServeCommand::class,
        'StubPublish' => StubPublishCommand::class,
        'ViewMake' => ViewMakeCommand::class,
    ];

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerCommandsWithDependencies();

        $this->registerCommands(array_merge(
            $this->commands,
            $this->devCommands
        ));

        Signals::resolveAvailabilityUsing(function () {
            return $this->app->runningInConsole()
                && ! $this->app->runningUnitTests()
                && extension_loaded('pcntl');
        });
    }

    /**
     * Register the given commands.
     *
     * @param  array  $commands
     * @return void
     */
    protected function registerCommands(array $commands)
    {
        foreach ($commands as $commandName => $command) {
            $method = "register{$commandName}Command";

            if (method_exists($this, $method)) {
                $this->{$method}();
            } else {
                $this->app->singleton($command);
            }
        }

        $this->commands(array_values($commands));
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerQueueForgetCommand()
    {
        $this->app->singleton(ForgetFailedQueueCommand::class);
    }

    /**
     * Register commands that require dependency injection.
     *
     * @return void
     */
    protected function registerCommandsWithDependencies()
    {
        foreach ($this->registerCommandsWithDependencies as $class => $dependencies) {
            $this->app->singleton($class, fn ($app) => $this->resolveDependencies($app, $dependencies, $class));
        }

        $this->commands(array_keys($this->registerCommandsWithDependencies));
    }

    /**
     * Resolve and inject dependencies for a given command class.
     *
     * @param  \Illuminate\Contracts\Container\Container  $app
     * @param  array  $dependencies
     * @param  string  $class
     * @return object
     */
    protected function resolveDependencies($app, $dependencies, $class)
    {
        $resolvedDependencies = [];
        foreach ($dependencies as $dep) {
            $resolvedDependencies[] = $app[$dep];
        }

        return new $class(...$resolvedDependencies);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array_merge(array_values($this->commands), array_values($this->devCommands), array_keys($this->registerCommandsWithDependencies));
    }
}
