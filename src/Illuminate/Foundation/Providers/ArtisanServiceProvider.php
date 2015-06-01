<?php

namespace Illuminate\Foundation\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Console\UpCommand;
use Illuminate\Foundation\Console\DownCommand;
use Illuminate\Foundation\Console\ServeCommand;
use Illuminate\Foundation\Console\TinkerCommand;
use Illuminate\Foundation\Console\JobMakeCommand;
use Illuminate\Foundation\Console\AppNameCommand;
use Illuminate\Foundation\Console\OptimizeCommand;
use Illuminate\Foundation\Console\RouteListCommand;
use Illuminate\Foundation\Console\EventMakeCommand;
use Illuminate\Foundation\Console\ModelMakeCommand;
use Illuminate\Foundation\Console\ViewClearCommand;
use Illuminate\Foundation\Console\RouteCacheCommand;
use Illuminate\Foundation\Console\RouteClearCommand;
use Illuminate\Foundation\Console\CommandMakeCommand;
use Illuminate\Foundation\Console\ConfigCacheCommand;
use Illuminate\Foundation\Console\ConfigClearCommand;
use Illuminate\Foundation\Console\ConsoleMakeCommand;
use Illuminate\Foundation\Console\EnvironmentCommand;
use Illuminate\Foundation\Console\KeyGenerateCommand;
use Illuminate\Foundation\Console\RequestMakeCommand;
use Illuminate\Foundation\Console\ListenerMakeCommand;
use Illuminate\Foundation\Console\ProviderMakeCommand;
use Illuminate\Foundation\Console\HandlerEventCommand;
use Illuminate\Foundation\Console\ClearCompiledCommand;
use Illuminate\Foundation\Console\EventGenerateCommand;
use Illuminate\Foundation\Console\VendorPublishCommand;
use Illuminate\Foundation\Console\HandlerCommandCommand;

class ArtisanServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $commands = [
        'AppName' => 'command.app.name',
        'ClearCompiled' => 'command.clear-compiled',
        'CommandMake' => 'command.command.make',
        'ConfigCache' => 'command.config.cache',
        'ConfigClear' => 'command.config.clear',
        'ConsoleMake' => 'command.console.make',
        'EventGenerate' => 'command.event.generate',
        'EventMake' => 'command.event.make',
        'Down' => 'command.down',
        'Environment' => 'command.environment',
        'HandlerCommand' => 'command.handler.command',
        'HandlerEvent' => 'command.handler.event',
        'JobMake' => 'command.job.make',
        'KeyGenerate' => 'command.key.generate',
        'ListenerMake' => 'command.listener.make',
        'ModelMake' => 'command.model.make',
        'Optimize' => 'command.optimize',
        'ProviderMake' => 'command.provider.make',
        'RequestMake' => 'command.request.make',
        'RouteCache' => 'command.route.cache',
        'RouteClear' => 'command.route.clear',
        'RouteList' => 'command.route.list',
        'Serve' => 'command.serve',
        'Tinker' => 'command.tinker',
        'Up' => 'command.up',
        'VendorPublish' => 'command.vendor.publish',
        'ViewClear' => 'command.view.clear',
    ];

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        foreach (array_keys($this->commands) as $command) {
            $method = "register{$command}Command";

            call_user_func_array([$this, $method], []);
        }

        $this->commands(array_values($this->commands));
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerAppNameCommand()
    {
        $this->app->singleton('command.app.name', function ($app) {
            return new AppNameCommand($app['composer'], $app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerClearCompiledCommand()
    {
        $this->app->singleton('command.clear-compiled', function () {
            return new ClearCompiledCommand;
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerCommandMakeCommand()
    {
        $this->app->singleton('command.command.make', function ($app) {
            return new CommandMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerConfigCacheCommand()
    {
        $this->app->singleton('command.config.cache', function ($app) {
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
        $this->app->singleton('command.config.clear', function ($app) {
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
        $this->app->singleton('command.console.make', function ($app) {
            return new ConsoleMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerEventGenerateCommand()
    {
        $this->app->singleton('command.event.generate', function () {
            return new EventGenerateCommand;
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerEventMakeCommand()
    {
        $this->app->singleton('command.event.make', function ($app) {
            return new EventMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerDownCommand()
    {
        $this->app->singleton('command.down', function () {
            return new DownCommand;
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerEnvironmentCommand()
    {
        $this->app->singleton('command.environment', function () {
            return new EnvironmentCommand;
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerHandlerCommandCommand()
    {
        $this->app->singleton('command.handler.command', function ($app) {
            return new HandlerCommandCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerHandlerEventCommand()
    {
        $this->app->singleton('command.handler.event', function ($app) {
            return new HandlerEventCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerJobMakeCommand()
    {
        $this->app->singleton('command.job.make', function ($app) {
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
        $this->app->singleton('command.key.generate', function () {
            return new KeyGenerateCommand;
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerListenerMakeCommand()
    {
        $this->app->singleton('command.listener.make', function ($app) {
            return new ListenerMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerModelMakeCommand()
    {
        $this->app->singleton('command.model.make', function ($app) {
            return new ModelMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerOptimizeCommand()
    {
        $this->app->singleton('command.optimize', function ($app) {
            return new OptimizeCommand($app['composer']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerProviderMakeCommand()
    {
        $this->app->singleton('command.provider.make', function ($app) {
            return new ProviderMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerRequestMakeCommand()
    {
        $this->app->singleton('command.request.make', function ($app) {
            return new RequestMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerRouteCacheCommand()
    {
        $this->app->singleton('command.route.cache', function ($app) {
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
        $this->app->singleton('command.route.clear', function ($app) {
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
        $this->app->singleton('command.route.list', function ($app) {
            return new RouteListCommand($app['router']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerServeCommand()
    {
        $this->app->singleton('command.serve', function () {
            return new ServeCommand;
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerTinkerCommand()
    {
        $this->app->singleton('command.tinker', function () {
            return new TinkerCommand;
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerUpCommand()
    {
        $this->app->singleton('command.up', function () {
            return new UpCommand;
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerVendorPublishCommand()
    {
        $this->app->singleton('command.vendor.publish', function ($app) {
            return new VendorPublishCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerViewClearCommand()
    {
        $this->app->singleton('command.view.clear', function ($app) {
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
        return array_values($this->commands);
    }
}
