<?php

namespace Illuminate\Support;

use Closure;
use Illuminate\Console\Application as Artisan;
use Illuminate\Contracts\Foundation\CachesConfiguration;
use Illuminate\Contracts\Foundation\CachesRoutes;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\View\Compilers\BladeCompiler;

abstract class ServiceProvider
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * All of the registered booting callbacks.
     *
     * @var array
     */
    protected $bootingCallbacks = [];

    /**
     * All of the registered booted callbacks.
     *
     * @var array
     */
    protected $bootedCallbacks = [];

    /**
     * The paths that should be published.
     *
     * @var array
     */
    public static $publishes = [];

    /**
     * The paths that should be published by group.
     *
     * @var array
     */
    public static $publishGroups = [];

    /**
     * Create a new service provider instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Register a booting callback to be run before the "boot" method is called.
     *
     * @param  \Closure  $callback
     * @return void
     */
    public function booting(Closure $callback)
    {
        $this->bootingCallbacks[] = $callback;
    }

    /**
     * Register a booted callback to be run after the "boot" method is called.
     *
     * @param  \Closure  $callback
     * @return void
     */
    public function booted(Closure $callback)
    {
        $this->bootedCallbacks[] = $callback;
    }

    /**
     * Call the registered booting callbacks.
     *
     * @return void
     */
    public function callBootingCallbacks()
    {
        $index = 0;

        while ($index < count($this->bootingCallbacks)) {
            $this->app->call($this->bootingCallbacks[$index]);

            $index++;
        }
    }

    /**
     * Call the registered booted callbacks.
     *
     * @return void
     */
    public function callBootedCallbacks()
    {
        $index = 0;

        while ($index < count($this->bootedCallbacks)) {
            $this->app->call($this->bootedCallbacks[$index]);

            $index++;
        }
    }

    /**
     * Merge the given configuration with the existing configuration.
     *
     * @param  string  $path
     * @param  string  $key
     * @return void
     */
    protected function mergeConfigFrom($path, $key)
    {
        if (! ($this->app instanceof CachesConfiguration && $this->app->configurationIsCached())) {
            $config = $this->app->make('config');

            $config->set($key, array_merge(
                require $path, $config->get($key, [])
            ));
        }
    }

    /**
     * Load the given routes file if routes are not already cached.
     *
     * @param  string  $path
     * @return void
     */
    protected function loadRoutesFrom($path)
    {
        if (! ($this->app instanceof CachesRoutes && $this->app->routesAreCached())) {
            require $path;
        }
    }

    /**
     * Register a view file namespace.
     *
     * @param  string|array  $path
     * @param  string  $namespace
     * @return void
     */
    protected function loadViewsFrom($path, $namespace)
    {
        $this->callAfterResolving('view', function ($view) use ($path, $namespace) {
            if (isset($this->app->config['view']['paths']) &&
                is_array($this->app->config['view']['paths'])) {
                foreach ($this->app->config['view']['paths'] as $viewPath) {
                    if (is_dir($appPath = $viewPath.'/vendor/'.$namespace)) {
                        $view->addNamespace($namespace, $appPath);
                    }
                }
            }

            $view->addNamespace($namespace, $path);
        });
    }

    /**
     * Register the given view components with a custom prefix.
     *
     * @param  string  $prefix
     * @param  array  $components
     * @return void
     */
    protected function loadViewComponentsAs($prefix, array $components)
    {
        $this->callAfterResolving(BladeCompiler::class, function ($blade) use ($prefix, $components) {
            foreach ($components as $alias => $component) {
                $blade->component($component, is_string($alias) ? $alias : null, $prefix);
            }
        });
    }

    /**
     * Register a translation file namespace.
     *
     * @param  string  $path
     * @param  string  $namespace
     * @return void
     */
    protected function loadTranslationsFrom($path, $namespace)
    {
        $this->callAfterResolving('translator', function ($translator) use ($path, $namespace) {
            $translator->addNamespace($namespace, $path);
        });
    }

    /**
     * Register a JSON translation file path.
     *
     * @param  string  $path
     * @return void
     */
    protected function loadJsonTranslationsFrom($path)
    {
        $this->callAfterResolving('translator', function ($translator) use ($path) {
            $translator->addJsonPath($path);
        });
    }

    /**
     * Register database migration paths.
     *
     * @param  array|string  $paths
     * @return void
     */
    protected function loadMigrationsFrom($paths)
    {
        $this->callAfterResolving('migrator', function ($migrator) use ($paths) {
            foreach ((array) $paths as $path) {
                $migrator->path($path);
            }
        });
    }

    /**
     * Setup an after resolving listener, or fire immediately if already resolved.
     *
     * @param  string  $name
     * @param  callable  $callback
     * @return void
     */
    protected function callAfterResolving($name, $callback)
    {
        $this->app->afterResolving($name, $callback);

        if ($this->app->resolved($name)) {
            $callback($this->app->make($name), $this->app);
        }
    }

    /**
     * Register paths to be published by the publish command.
     *
     * @param  array  $paths
     * @param  mixed  $groups
     * @return void
     */
    protected function publishes(array $paths, $groups = null)
    {
        $this->ensurePublishArrayInitialized($class = static::class);

        static::$publishes[$class] = array_merge(static::$publishes[$class], $paths);

        foreach ((array) $groups as $group) {
            $this->addPublishGroup($group, $paths);
        }
    }

    /**
     * Ensure the publish array for the service provider is initialized.
     *
     * @param  string  $class
     * @return void
     */
    protected function ensurePublishArrayInitialized($class)
    {
        if (! array_key_exists($class, static::$publishes)) {
            static::$publishes[$class] = [];
        }
    }

    /**
     * Add a publish group / tag to the service provider.
     *
     * @param  string  $group
     * @param  array  $paths
     * @return void
     */
    protected function addPublishGroup($group, $paths)
    {
        if (! array_key_exists($group, static::$publishGroups)) {
            static::$publishGroups[$group] = [];
        }

        static::$publishGroups[$group] = array_merge(
            static::$publishGroups[$group], $paths
        );
    }

    /**
     * Get the paths to publish.
     *
     * @param  string|null  $provider
     * @param  string|null  $group
     * @return array
     */
    public static function pathsToPublish($provider = null, $group = null)
    {
        if (! is_null($paths = static::pathsForProviderOrGroup($provider, $group))) {
            return $paths;
        }

        return collect(static::$publishes)->reduce(function ($paths, $p) {
            return array_merge($paths, $p);
        }, []);
    }

    /**
     * Get the paths for the provider or group (or both).
     *
     * @param  string|null  $provider
     * @param  string|null  $group
     * @return array
     */
    protected static function pathsForProviderOrGroup($provider, $group)
    {
        if ($provider && $group) {
            return static::pathsForProviderAndGroup($provider, $group);
        } elseif ($group && array_key_exists($group, static::$publishGroups)) {
            return static::$publishGroups[$group];
        } elseif ($provider && array_key_exists($provider, static::$publishes)) {
            return static::$publishes[$provider];
        } elseif ($group || $provider) {
            return [];
        }
    }

    /**
     * Get the paths for the provider and group.
     *
     * @param  string  $provider
     * @param  string  $group
     * @return array
     */
    protected static function pathsForProviderAndGroup($provider, $group)
    {
        if (! empty(static::$publishes[$provider]) && ! empty(static::$publishGroups[$group])) {
            return array_intersect_key(static::$publishes[$provider], static::$publishGroups[$group]);
        }

        return [];
    }

    /**
     * Get the service providers available for publishing.
     *
     * @return array
     */
    public static function publishableProviders()
    {
        return array_keys(static::$publishes);
    }

    /**
     * Get the groups available for publishing.
     *
     * @return array
     */
    public static function publishableGroups()
    {
        return array_keys(static::$publishGroups);
    }

    /**
     * Register the package's custom Artisan commands.
     *
     * @param  array|mixed  $commands
     * @return void
     */
    public function commands($commands)
    {
        $commands = is_array($commands) ? $commands : func_get_args();

        Artisan::starting(function ($artisan) use ($commands) {
            $artisan->resolveCommands($commands);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }

    /**
     * Get the events that trigger this service provider to register.
     *
     * @return array
     */
    public function when()
    {
        return [];
    }

    /**
     * Determine if the provider is deferred.
     *
     * @return bool
     */
    public function isDeferred()
    {
        return $this instanceof DeferrableProvider;
    }
}
