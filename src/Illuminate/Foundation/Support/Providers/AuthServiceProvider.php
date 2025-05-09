<?php

namespace Illuminate\Foundation\Support\Providers;

use Illuminate\Foundation\Auth\DiscoverPolicies;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [];

    /** Indicates if polices should be discovered. */
    protected static bool $shouldDiscoverPolices = true;

    /**
     * The configured class discovery paths.
     *
     * @var iterable<int, string>|null
     */
    protected static ?iterable $classDiscoveryPaths = null;

    /**
     * Register the application's policies.
     *
     * @return void
     */
    public function register()
    {
        $this->booting(function () {
            $this->registerPolicies();
        });
    }

    /**
     * Register the application's policies.
     *
     * @return void
     */
    public function registerPolicies()
    {
        foreach ($this->getPolicies() as $class => $policy) {
            Gate::policy($class, $policy);
        }
    }

    /**
     * Get the policies defined on the provider.
     *
     * @return array<class-string, class-string>
     */
    public function policies()
    {
        return $this->policies;
    }

    /**
     * Get the policies for the application.
     *
     * @return array<class-string, class-string>
     */
    public function getPolicies(): array
    {
        if ($this->app->policiesAreCached()) {
            $cache = require $this->app->getCachedPoliciesPath();

            return $cache[$this::class] ?? [];
        } else {
            return [
                ...$this->discoveredPolicies(),
                ...$this->policies(),
            ];
        }
    }

    /**
     * Get the discovered policies for the application.
     *
     * @return array<class-string, class-string>
     */
    public function discoveredPolicies(): array
    {
        return $this->shouldDiscoverPolices()
            ? $this->discoverPolicies()
            : [];
    }

    /** Determine if polices should be automatically discovered. */
    public function shouldDiscoverPolices(): bool
    {
        return $this::class === __CLASS__ && static::$shouldDiscoverPolices;
    }

    /**
     * Discover the polices for the application.
     *
     * @return array<class-string, class-string>
     */
    public function discoverPolicies(): array
    {
        return (new LazyCollection($this->discoverClassesWithin()))
            ->flatMap(fn ($directory) => glob($directory, GLOB_ONLYDIR))
            ->reject(fn ($directory) => ! is_dir($directory))
            ->pipe(fn ($directories) => DiscoverPolicies::within(
                $directories->all(),
                $this->classDiscoveryBasePath()
            ));
    }

    /**
     * Get the class directories that should be used to discover polices.
     *
     * @return iterable<int, string>
     */
    protected function discoverClassesWithin(): iterable
    {
        return static::$classDiscoveryPaths ?? [$this->app->path('Models')];
    }

    /** Get the base path to be used during policy discovery. */
    protected function classDiscoveryBasePath(): string
    {
        return base_path();
    }

    /**
     * Add the given class discovery paths to the application's class discovery paths.
     *
     * @param  iterable<int, string>|string  $paths
     */
    public static function addClassDiscoveryPaths(iterable|string $paths): void
    {
        $paths = is_string($paths) ? [$paths] : $paths;

        static::$classDiscoveryPaths = (new LazyCollection(static::$classDiscoveryPaths))
            ->merge($paths)
            ->unique()
            ->values();
    }

    /**
     * Set the globally configured class discovery paths.
     *
     * @param  iterable<int, string>  $paths
     */
    public static function setClassDiscoveryPaths(iterable $paths): void
    {
        static::$classDiscoveryPaths = $paths;
    }

    /** Disable policy discovery for the application. */
    public static function disablePolicyDiscovery(): void
    {
        static::$shouldDiscoverPolices = false;
    }
}
