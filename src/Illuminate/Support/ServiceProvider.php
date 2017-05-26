<?php

namespace Illuminate\Support;

use Illuminate\Console\Application as Artisan;

abstract class ServiceProvider
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Indicates whether the default view locations have been registered.
     *
     * @var bool
     */
    protected $viewNamespacesRegistered = false;

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
     * Merge the given configuration with the existing configuration.
     *
     * @param  string  $path
     * @param  string  $key
     * @param  bool  $publish
     * @return void
     */
    protected function mergeConfigFrom($path, $key, $publish = false)
    {
        $config = $this->app['config']->get($key, []);

        $this->app['config']->set($key, array_merge(require $path, $config));

        if ($publish) {
            $this->publishesConfig($path, $key);
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
        if (! $this->app->routesAreCached()) {
            require $path;
        }
    }

    /**
     * Register a view file namespace.
     *
     * @param  string  $path
     * @param  string  $namespace
     * @param  bool  $publish
     * @return void
     */
    protected function loadViewsFrom($path, $namespace, $publish = false)
    {
        if ($publish) {
            $this->publishesViews($path, $namespace);
        }

        $this->registerAlternateViewNamespaces($namespace);
        $this->app['view']->addNamespace($namespace, $path);
    }

    /**
     * @param  string  $namespace
     * @return void
     */
    protected function registerAlternateViewNamespaces($namespace)
    {
        if ($this->viewNamespacesRegistered) {
            return;
        }

        $viewPaths = $this->app['config']->get('view.paths', []);
        foreach ($viewPaths as $viewPath) {
            $viewPath = rtrim($viewPath, '/').'/vendor/'.$namespace;
            $this->app['view']->addNamespace($namespace, $viewPath);
        }

        $this->viewNamespacesRegistered = true;
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
        $this->app['translator']->addNamespace($namespace, $path);
    }

    /**
     * Register a database migration path.
     *
     * @param  array|string  $paths
     * @return void
     */
    protected function loadMigrationsFrom($paths)
    {
        $this->app->afterResolving('migrator', function ($migrator) use ($paths) {
            foreach ((array) $paths as $path) {
                $migrator->path($path);
            }
        });
    }

    /**
     * Register paths to be published by the publish command.
     *
     * @param  array  $paths
     * @param  string  $group
     * @return void
     */
    protected function publishes(array $paths, $group = null)
    {
        $this->ensurePublishArrayInitialized($class = static::class);

        static::$publishes[$class] = array_merge(static::$publishes[$class], $paths);

        if ($group) {
            $this->addPublishGroup($group, $paths);
        }
    }

    /**
     * Register a view path to be published to the app's configured view directory.
     *
     * @param $path
     * @param $namespace
     * @param null $group
     */
    protected function publishesViews($path, $namespace, $group = null)
    {
        $viewPaths = $this->app['config']->get('view.paths', [resource_path('views')]);
        $this->publishes([
            $path => rtrim($viewPaths[0], '/')."/vendor/$namespace",
        ], $group);
    }

    /**
     * Register a config file to be published to the app's config directory.
     *
     * @param $path
     * @param null $namespace
     */
    protected function publishesConfig($path, $namespace = null, $group = null)
    {
        if (null === $namespace) {
            $namespace = basename($path, '.php');
        }

        $this->publishes([$path => config_path("$namespace.php")], $group);
    }

    /**
     * Register a path to be published to the app's lang directory.
     *
     * @param  string  $path
     * @param  string  $namespace
     * @param  string  $group
     * @return void
     */
    protected function publishesTranslations($path, $namespace, $group = null)
    {
        $langPath = method_exists($this->app, 'langPath')
            ? $this->app->langPath()
            : resource_path('lang');

        $this->publishes([$path => rtrim($langPath, '/')."/vendor/$namespace"], $group);
    }

    /**
     * Register a path to be published to the app's public directory.
     *
     * @param  string  $path
     * @param  string  $namespace
     * @param  string  $group
     * @return void
     */
    protected function publishesPublicAssets($path, $namespace, $group = null)
    {
        $this->publishes([$path => public_path("/vendor/$namespace")], $group);
    }

    /**
     * Register a path to be published to the app's public directory.
     *
     * @param  string  $path
     * @param  string  $group
     * @return void
     */
    protected function publishesMigrations($path, $group = null)
    {
        $this->publishes([$path => database_path('migrations')], $group);
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
     * @param  string  $provider
     * @param  string  $group
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
     * Get the paths for the provdider and group.
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
        return $this->defer;
    }
}
