<?php

namespace Illuminate\Support\Facades;

/**
 * @method static string version() Get the version number of the application.
 * @method static void bootstrapWith() Run the given array of bootstrap classes.
 * @method static void afterLoadingEnvironment() Register a callback to run after loading the environment.
 * @method static void beforeBootstrapping() Register a callback to run before a bootstrapper.
 * @method static void afterBootstrapping() Register a callback to run after a bootstrapper.
 * @method static bool hasBeenBootstrapped() Determine if the application has been bootstrapped before.
 * @method static $this setBasePath() Set the base path for the application.
 * @method static string path() Get the path to the application "app" directory.
 * @method static string basePath() Get the base path of the Laravel installation.
 * @method static string bootstrapPath() Get the path to the bootstrap directory.
 * @method static string configPath() Get the path to the application configuration files.
 * @method static string databasePath() Get the path to the database directory.
 * @method static $this useDatabasePath() Set the database directory.
 * @method static string langPath() Get the path to the language files.
 * @method static string publicPath() Get the path to the public / web directory.
 * @method static string storagePath() Get the path to the storage directory.
 * @method static $this useStoragePath() Set the storage directory.
 * @method static string resourcePath() Get the path to the resources directory.
 * @method static string environmentPath() Get the path to the environment file directory.
 * @method static $this useEnvironmentPath() Set the directory for the environment file.
 * @method static $this loadEnvironmentFrom() Set the environment file to be loaded during bootstrapping.
 * @method static string environmentFile() Get the environment file the application is using.
 * @method static string environmentFilePath() Get the fully qualified path to the environment file.
 * @method static string|bool environment() Get or check the current application environment.
 * @method static bool isLocal() Determine if application is in local environment.
 * @method static string detectEnvironment() Detect the application's current environment.
 * @method static bool runningInConsole() Determine if we are running in the console.
 * @method static bool runningUnitTests() Determine if we are running unit tests.
 * @method static void registerConfiguredProviders() Register all of the configured providers.
 * @method static \Illuminate\Support\ServiceProvider register($provider, $options = array()) Register a service provider with the application.
 * @method static \Illuminate\Support\ServiceProvider|null getProvider() Get the registered service provider instance if it exists.
 * @method static \Illuminate\Support\ServiceProvider resolveProvider() Resolve a service provider instance from the class name.
 * @method static void loadDeferredProviders() Load and boot all of the remaining deferred providers.
 * @method static void loadDeferredProvider() Load the provider for a deferred service.
 * @method static void registerDeferredProvider() Register a deferred provider and service.
 * @method static mixed make($abstract, $parameters = array()) Resolve the given type from the container.
 * @method static bool bound() Determine if the given abstract type has been bound.
 * @method static bool isBooted() Determine if the application has booted.
 * @method static void boot() Boot the application's service providers.
 * @method static void booting() Register a new boot listener.
 * @method static void booted() Register a new "booted" listener.
 * @method static bool shouldSkipMiddleware() Determine if middleware has been disabled for the application.
 * @method static string getCachedServicesPath() Get the path to the cached services.php file.
 * @method static string getCachedPackagesPath() Get the path to the cached packages.php file.
 * @method static bool configurationIsCached() Determine if the application configuration is cached.
 * @method static string getCachedConfigPath() Get the path to the configuration cache file.
 * @method static bool routesAreCached() Determine if the application routes are cached.
 * @method static string getCachedRoutesPath() Get the path to the routes cache file.
 * @method static bool isDownForMaintenance() Determine if the application is currently down for maintenance.
 * @method static void abort(int $code, string $message, array $headers) Throw an HttpException with the given data.
 * @method static $this terminating() Register a terminating callback with the application.
 * @method static void terminate() Terminate the application.
 * @method static array getLoadedProviders() Get the service providers that have been loaded.
 * @method static array getDeferredServices() Get the application's deferred services.
 * @method static void setDeferredServices() Set the application's deferred services.
 * @method static void addDeferredServices() Add an array of services to the application's deferred services.
 * @method static bool isDeferredService() Determine if the given service is a deferred service.
 * @method static void provideFacades() Configure the real-time facade namespace.
 * @method static $this configureMonologUsing() Define a callback to be used to configure Monolog.
 * @method static bool hasMonologConfigurator() Determine if the application has a custom Monolog configurator.
 * @method static callable getMonologConfigurator() Get the custom Monolog configurator for the application.
 * @method static string getLocale() Get the current application locale.
 * @method static void setLocale(string $locale) Set the current application locale.
 * @method static bool isLocale(string $locale) Determine if application locale is the given locale.
 * @method static void registerCoreContainerAliases() Register the core class aliases in the container.
 * @method static void flush() Flush the container of all bindings and resolved instances.
 * @method static string getNamespace() Get the application namespace.
 * @method static \Illuminate\Contracts\Container\ContextualBindingBuilder when(string $concrete) Define a contextual binding.
 * @method static bool has(string $id) Returns true if the container can return an entry for the given identifier.
 * @method static bool resolved(string $abstract) Determine if the given abstract type has been resolved.
 * @method static bool isShared(string $abstract) Determine if a given type is shared.
 * @method static bool isAlias(string $name) Determine if a given string is an alias.
 * @method static void bind(string | array $abstract, \Closure | string | null $concrete, bool $shared) Register a binding with the container.
 * @method static bool hasMethodBinding(string $method) Determine if the container has a method binding.
 * @method static void bindMethod(string $method, \Closure $callback) Bind a callback to resolve with Container::call.
 * @method static mixed callMethodBinding(string $method, mixed $instance) Get the method binding for the given method.
 * @method static void addContextualBinding(string $concrete, string $abstract, \Closure | string $implementation) Add a contextual binding to the container.
 * @method static void bindIf(string $abstract, \Closure | string | null $concrete, bool $shared) Register a binding if it hasn't already been registered.
 * @method static void singleton(string | array $abstract, \Closure | string | null $concrete) Register a shared binding in the container.
 * @method static void extend(string $abstract, \Closure $closure) "Extend" an abstract type in the container.
 * @method static mixed instance(string $abstract, mixed $instance) Register an existing instance as shared in the container.
 * @method static void tag(array | string $abstracts, array | mixed $tags) Assign a set of tags to a given binding.
 * @method static array tagged(string $tag) Resolve all of the bindings for a given tag.
 * @method static void alias(string $abstract, string $alias) Alias a type to a different name.
 * @method static mixed rebinding(string $abstract, \Closure $callback) Bind a new callback to an abstract 's rebind event.
 * @method static mixed refresh(string $abstract, mixed $target, string $method) Refresh an instance on the given target and method.
 * @method static \Closure wrap(\Closure $callback, array $parameters) Wrap the given closure such that its dependencies will be injected when executed.
 * @method static mixed call(callable | string $callback, array $parameters, string | null $defaultMethod) Call the given Closure / class@method and inject its dependencies.
 * @method static \Closure factory(string $abstract) Get a closure to resolve the given type from the container.
 * @method static mixed makeWith(string $abstract, array $parameters) An alias function name for make().
 * @method static mixed Entry. get(string $id,) Finds an entry of the container by its identifier and returns it.
 * @method static mixed build(string $concrete) Instantiate a concrete instance of the given type.
 * @method static void resolving(string $abstract, \Closure | null $callback) Register a new resolving callback.
 * @method static void afterResolving(string $abstract, \Closure | null $callback) Register a new after resolving callback for all types.
 * @method static array getBindings() Get the container's bindings.
 * @method static string getAlias(string $abstract) Get the alias for an abstract if available.
 * @method static void forgetExtenders(string $abstract) Remove all of the extender callbacks for a given type.
 * @method static void forgetInstance(string $abstract) Remove a resolved instance from the instance cache.
 * @method static void forgetInstances() Clear all of the instances from the container.
 * @method static getInstance() Set the globally available instance of the container.
 * @method static setInstance(\Illuminate\Contracts\Container\Container | null $container) Set the shared instance of the container.
 * @method static bool offsetExists(string $key) Determine if a given offset exists.
 * @method static mixed offsetGet(string $key) Get the value at a given offset.
 * @method static void offsetSet(string $key, mixed $value) Set the value at a given offset.
 * @method static void offsetUnset(string $key) Unset the value at a given offset.
 * @see \Illuminate\Foundation\Application
 */
class App extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'app';
    }
}
