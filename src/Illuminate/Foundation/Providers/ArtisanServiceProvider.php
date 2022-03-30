<?php

namespace Illuminate\Foundation\Providers;

use Illuminate\Auth\Console\ClearResetsCommand;
use Illuminate\Cache\Console\CacheTableCommand;
use Illuminate\Cache\Console\ClearCommand as CacheClearCommand;
use Illuminate\Cache\Console\ForgetCommand as CacheForgetCommand;
use Illuminate\Console\Scheduling\ScheduleClearCacheCommand;
use Illuminate\Console\Scheduling\ScheduleFinishCommand;
use Illuminate\Console\Scheduling\ScheduleListCommand;
use Illuminate\Console\Scheduling\ScheduleRunCommand;
use Illuminate\Console\Scheduling\ScheduleTestCommand;
use Illuminate\Console\Scheduling\ScheduleWorkCommand;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Database\Console\DbCommand;
use Illuminate\Database\Console\DumpCommand;
use Illuminate\Database\Console\Factories\FactoryMakeCommand;
use Illuminate\Database\Console\PruneCommand;
use Illuminate\Database\Console\Seeds\SeedCommand;
use Illuminate\Database\Console\Seeds\SeederMakeCommand;
use Illuminate\Database\Console\WipeCommand;
use Illuminate\Foundation\Console\CastMakeCommand;
use Illuminate\Foundation\Console\ChannelMakeCommand;
use Illuminate\Foundation\Console\ClearCompiledCommand;
use Illuminate\Foundation\Console\ComponentMakeCommand;
use Illuminate\Foundation\Console\ConfigCacheCommand;
use Illuminate\Foundation\Console\ConfigClearCommand;
use Illuminate\Foundation\Console\ConsoleMakeCommand;
use Illuminate\Foundation\Console\DownCommand;
use Illuminate\Foundation\Console\EnvironmentCommand;
use Illuminate\Foundation\Console\EventCacheCommand;
use Illuminate\Foundation\Console\EventClearCommand;
use Illuminate\Foundation\Console\EventGenerateCommand;
use Illuminate\Foundation\Console\EventListCommand;
use Illuminate\Foundation\Console\EventMakeCommand;
use Illuminate\Foundation\Console\ExceptionMakeCommand;
use Illuminate\Foundation\Console\JobMakeCommand;
use Illuminate\Foundation\Console\KeyGenerateCommand;
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
use Illuminate\Foundation\Console\StubPublishCommand;
use Illuminate\Foundation\Console\TestMakeCommand;
use Illuminate\Foundation\Console\UpCommand;
use Illuminate\Foundation\Console\VendorPublishCommand;
use Illuminate\Foundation\Console\ViewCacheCommand;
use Illuminate\Foundation\Console\ViewClearCommand;
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
    protected $commands = [
        'CacheClear' => CacheClearCommand::class,
        'CacheForget' => CacheForgetCommand::class,
        'ClearCompiled' => ClearCompiledCommand::class,
        'ClearResets' => ClearResetsCommand::class,
        'ConfigCache' => ConfigCacheCommand::class,
        'ConfigClear' => ConfigClearCommand::class,
        'Db' => DbCommand::class,
        'DbPrune' => PruneCommand::class,
        'DbWipe' => WipeCommand::class,
        'Down' => DownCommand::class,
        'Environment' => EnvironmentCommand::class,
        'EventCache' => EventCacheCommand::class,
        'EventClear' => EventClearCommand::class,
        'EventList' => EventListCommand::class,
        'KeyGenerate' => KeyGenerateCommand::class,
        'Optimize' => OptimizeCommand::class,
        'OptimizeClear' => OptimizeClearCommand::class,
        'PackageDiscover' => PackageDiscoverCommand::class,
        'QueueClear' => QueueClearCommand::class,
        'QueueFailed' => ListFailedQueueCommand::class,
        'QueueFlush' => FlushFailedQueueCommand::class,
        'QueueForget' => ForgetFailedQueueCommand::class,
        'QueueListen' => QueueListenCommand::class,
        'QueueMonitor' => QueueMonitorCommand::class,
        'QueuePruneBatches' => QueuePruneBatchesCommand::class,
        'QueuePruneFailedJobs' => QueuePruneFailedJobsCommand::class,
        'QueueRestart' => QueueRestartCommand::class,
        'QueueRetry' => QueueRetryCommand::class,
        'QueueRetryBatch' => QueueRetryBatchCommand::class,
        'QueueWork' => QueueWorkCommand::class,
        'RouteCache' => RouteCacheCommand::class,
        'RouteClear' => RouteClearCommand::class,
        'RouteList' => RouteListCommand::class,
        'SchemaDump' => DumpCommand::class,
        'Seed' => SeedCommand::class,
        'ScheduleFinish' => ScheduleFinishCommand::class,
        'ScheduleList' => ScheduleListCommand::class,
        'ScheduleRun' => ScheduleRunCommand::class,
        'ScheduleClearCache' => ScheduleClearCacheCommand::class,
        'ScheduleTest' => ScheduleTestCommand::class,
        'ScheduleWork' => ScheduleWorkCommand::class,
        'StorageLink' => StorageLinkCommand::class,
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
        'CacheTable' => CacheTableCommand::class,
        'CastMake' => CastMakeCommand::class,
        'ChannelMake' => ChannelMakeCommand::class,
        'ComponentMake' => ComponentMakeCommand::class,
        'ConsoleMake' => ConsoleMakeCommand::class,
        'ControllerMake' => ControllerMakeCommand::class,
        'EventGenerate' => EventGenerateCommand::class,
        'EventMake' => EventMakeCommand::class,
        'ExceptionMake' => ExceptionMakeCommand::class,
        'FactoryMake' => FactoryMakeCommand::class,
        'JobMake' => JobMakeCommand::class,
        'ListenerMake' => ListenerMakeCommand::class,
        'MailMake' => MailMakeCommand::class,
        'MiddlewareMake' => MiddlewareMakeCommand::class,
        'ModelMake' => ModelMakeCommand::class,
        'NotificationMake' => NotificationMakeCommand::class,
        'NotificationTable' => NotificationTableCommand::class,
        'ObserverMake' => ObserverMakeCommand::class,
        'PolicyMake' => PolicyMakeCommand::class,
        'ProviderMake' => ProviderMakeCommand::class,
        'QueueFailedTable' => FailedTableCommand::class,
        'QueueTable' => TableCommand::class,
        'QueueBatchesTable' => BatchesTableCommand::class,
        'RequestMake' => RequestMakeCommand::class,
        'ResourceMake' => ResourceMakeCommand::class,
        'RuleMake' => RuleMakeCommand::class,
        'ScopeMake' => ScopeMakeCommand::class,
        'SeederMake' => SeederMakeCommand::class,
        'SessionTable' => SessionTableCommand::class,
        'Serve' => ServeCommand::class,
        'StubPublish' => StubPublishCommand::class,
        'TestMake' => TestMakeCommand::class,
        'VendorPublish' => VendorPublishCommand::class,
    ];

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerCommands(array_merge(
            $this->commands, $this->devCommands
        ));
    }

    /**
     * Register the given commands.
     *
     * @param  array  $commands
     * @return void
     */
    protected function registerCommands(array $commands)
    {
        foreach (array_keys($commands) as $command) {
            $this->{"register{$command}Command"}();
        }

        $this->commands(array_values($commands));
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerCacheClearCommand()
    {
        $this->app->singleton(CacheClearCommand::class, function ($app) {
            return new CacheClearCommand($app['cache'], $app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerCacheForgetCommand()
    {
        $this->app->singleton(CacheForgetCommand::class, function ($app) {
            return new CacheForgetCommand($app['cache']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerCacheTableCommand()
    {
        $this->app->singleton(CacheTableCommand::class, function ($app) {
            return new CacheTableCommand($app['files'], $app['composer']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerCastMakeCommand()
    {
        $this->app->singleton(CastMakeCommand::class, function ($app) {
            return new CastMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerChannelMakeCommand()
    {
        $this->app->singleton(ChannelMakeCommand::class, function ($app) {
            return new ChannelMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerClearCompiledCommand()
    {
        $this->app->singleton(ClearCompiledCommand::class);
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerClearResetsCommand()
    {
        $this->app->singleton(ClearResetsCommand::class);
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerComponentMakeCommand()
    {
        $this->app->singleton(ComponentMakeCommand::class, function ($app) {
            return new ComponentMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerConfigCacheCommand()
    {
        $this->app->singleton(ConfigCacheCommand::class, function ($app) {
            return new ConfigCacheCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerConfigClearCommand()
    {
        $this->app->singleton(ConfigClearCommand::class, function ($app) {
            return new ConfigClearCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerConsoleMakeCommand()
    {
        $this->app->singleton(ConsoleMakeCommand::class, function ($app) {
            return new ConsoleMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerControllerMakeCommand()
    {
        $this->app->singleton(ControllerMakeCommand::class, function ($app) {
            return new ControllerMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerDbCommand()
    {
        $this->app->singleton(DbCommand::class);
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerDbPruneCommand()
    {
        $this->app->singleton(PruneCommand::class);
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerDbWipeCommand()
    {
        $this->app->singleton(WipeCommand::class);
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerEventGenerateCommand()
    {
        $this->app->singleton(EventGenerateCommand::class);
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerEventMakeCommand()
    {
        $this->app->singleton(EventMakeCommand::class, function ($app) {
            return new EventMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerExceptionMakeCommand()
    {
        $this->app->singleton(ExceptionMakeCommand::class, function ($app) {
            return new ExceptionMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerFactoryMakeCommand()
    {
        $this->app->singleton(FactoryMakeCommand::class, function ($app) {
            return new FactoryMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerDownCommand()
    {
        $this->app->singleton(DownCommand::class);
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerEnvironmentCommand()
    {
        $this->app->singleton(EnvironmentCommand::class);
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerEventCacheCommand()
    {
        $this->app->singleton(EventCacheCommand::class);
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerEventClearCommand()
    {
        $this->app->singleton(EventClearCommand::class, function ($app) {
            return new EventClearCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerEventListCommand()
    {
        $this->app->singleton(EventListCommand::class);
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerJobMakeCommand()
    {
        $this->app->singleton(JobMakeCommand::class, function ($app) {
            return new JobMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerKeyGenerateCommand()
    {
        $this->app->singleton(KeyGenerateCommand::class);
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerListenerMakeCommand()
    {
        $this->app->singleton(ListenerMakeCommand::class, function ($app) {
            return new ListenerMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerMailMakeCommand()
    {
        $this->app->singleton(MailMakeCommand::class, function ($app) {
            return new MailMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerMiddlewareMakeCommand()
    {
        $this->app->singleton(MiddlewareMakeCommand::class, function ($app) {
            return new MiddlewareMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerModelMakeCommand()
    {
        $this->app->singleton(ModelMakeCommand::class, function ($app) {
            return new ModelMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerNotificationMakeCommand()
    {
        $this->app->singleton(NotificationMakeCommand::class, function ($app) {
            return new NotificationMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerNotificationTableCommand()
    {
        $this->app->singleton(NotificationTableCommand::class, function ($app) {
            return new NotificationTableCommand($app['files'], $app['composer']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerOptimizeCommand()
    {
        $this->app->singleton(OptimizeCommand::class);
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerObserverMakeCommand()
    {
        $this->app->singleton(ObserverMakeCommand::class, function ($app) {
            return new ObserverMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerOptimizeClearCommand()
    {
        $this->app->singleton(OptimizeClearCommand::class);
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerPackageDiscoverCommand()
    {
        $this->app->singleton(PackageDiscoverCommand::class);
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerPolicyMakeCommand()
    {
        $this->app->singleton(PolicyMakeCommand::class, function ($app) {
            return new PolicyMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerProviderMakeCommand()
    {
        $this->app->singleton(ProviderMakeCommand::class, function ($app) {
            return new ProviderMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerQueueFailedCommand()
    {
        $this->app->singleton(ListFailedQueueCommand::class);
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
     * Register the command.
     *
     * @return void
     */
    protected function registerQueueFlushCommand()
    {
        $this->app->singleton(FlushFailedQueueCommand::class);
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerQueueListenCommand()
    {
        $this->app->singleton(QueueListenCommand::class, function ($app) {
            return new QueueListenCommand($app['queue.listener']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerQueueMonitorCommand()
    {
        $this->app->singleton(QueueMonitorCommand::class, function ($app) {
            return new QueueMonitorCommand($app['queue'], $app['events']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerQueuePruneBatchesCommand()
    {
        $this->app->singleton(QueuePruneBatchesCommand::class, function () {
            return new QueuePruneBatchesCommand;
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerQueuePruneFailedJobsCommand()
    {
        $this->app->singleton(QueuePruneFailedJobsCommand::class, function () {
            return new QueuePruneFailedJobsCommand;
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerQueueRestartCommand()
    {
        $this->app->singleton(QueueRestartCommand::class, function ($app) {
            return new QueueRestartCommand($app['cache.store']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerQueueRetryCommand()
    {
        $this->app->singleton(QueueRetryCommand::class);
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerQueueRetryBatchCommand()
    {
        $this->app->singleton(QueueRetryBatchCommand::class);
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerQueueWorkCommand()
    {
        $this->app->singleton(QueueWorkCommand::class, function ($app) {
            return new QueueWorkCommand($app['queue.worker'], $app['cache.store']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerQueueClearCommand()
    {
        $this->app->singleton(QueueClearCommand::class);
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerQueueFailedTableCommand()
    {
        $this->app->singleton(FailedTableCommand::class, function ($app) {
            return new FailedTableCommand($app['files'], $app['composer']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerQueueTableCommand()
    {
        $this->app->singleton(TableCommand::class, function ($app) {
            return new TableCommand($app['files'], $app['composer']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerQueueBatchesTableCommand()
    {
        $this->app->singleton(BatchesTableCommand::class, function ($app) {
            return new BatchesTableCommand($app['files'], $app['composer']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerRequestMakeCommand()
    {
        $this->app->singleton(RequestMakeCommand::class, function ($app) {
            return new RequestMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerResourceMakeCommand()
    {
        $this->app->singleton(ResourceMakeCommand::class, function ($app) {
            return new ResourceMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerRuleMakeCommand()
    {
        $this->app->singleton(RuleMakeCommand::class, function ($app) {
            return new RuleMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerScopeMakeCommand()
    {
        $this->app->singleton(ScopeMakeCommand::class, function ($app) {
            return new ScopeMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerSeederMakeCommand()
    {
        $this->app->singleton(SeederMakeCommand::class, function ($app) {
            return new SeederMakeCommand($app['files'], $app['composer']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerSessionTableCommand()
    {
        $this->app->singleton(SessionTableCommand::class, function ($app) {
            return new SessionTableCommand($app['files'], $app['composer']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerStorageLinkCommand()
    {
        $this->app->singleton(StorageLinkCommand::class);
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerRouteCacheCommand()
    {
        $this->app->singleton(RouteCacheCommand::class, function ($app) {
            return new RouteCacheCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerRouteClearCommand()
    {
        $this->app->singleton(RouteClearCommand::class, function ($app) {
            return new RouteClearCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerRouteListCommand()
    {
        $this->app->singleton(RouteListCommand::class, function ($app) {
            return new RouteListCommand($app['router']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerSchemaDumpCommand()
    {
        $this->app->singleton(DumpCommand::class);
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerSeedCommand()
    {
        $this->app->singleton(SeedCommand::class, function ($app) {
            return new SeedCommand($app['db']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerScheduleClearCacheCommand()
    {
        $this->app->singleton(ScheduleClearCacheCommand::class);
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerScheduleFinishCommand()
    {
        $this->app->singleton(ScheduleFinishCommand::class);
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerScheduleListCommand()
    {
        $this->app->singleton(ScheduleListCommand::class);
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerScheduleRunCommand()
    {
        $this->app->singleton(ScheduleRunCommand::class);
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerScheduleTestCommand()
    {
        $this->app->singleton(ScheduleTestCommand::class);
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerScheduleWorkCommand()
    {
        $this->app->singleton(ScheduleWorkCommand::class);
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerServeCommand()
    {
        $this->app->singleton(ServeCommand::class);
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerStubPublishCommand()
    {
        $this->app->singleton(StubPublishCommand::class);
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerTestMakeCommand()
    {
        $this->app->singleton(TestMakeCommand::class, function ($app) {
            return new TestMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerUpCommand()
    {
        $this->app->singleton(UpCommand::class);
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerVendorPublishCommand()
    {
        $this->app->singleton(VendorPublishCommand::class, function ($app) {
            return new VendorPublishCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerViewCacheCommand()
    {
        $this->app->singleton(ViewCacheCommand::class);
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerViewClearCommand()
    {
        $this->app->singleton(ViewClearCommand::class, function ($app) {
            return new ViewClearCommand($app['files']);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array_merge(array_values($this->commands), array_values($this->devCommands));
    }
}
