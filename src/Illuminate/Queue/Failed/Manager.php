<?php

namespace Illuminate\Queue\Failed;

use Closure;

class Manager
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * Registered failed job providers.
     *
     * @var array
     */
    protected $providers = [];

    /**
     * Create a new queue failed manager instance.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
        $this->registerNullFailedJobProvider();
        $this->registerDatabaseFailedJobProvider();
    }

    /**
     * Return a failed job provider instance.
     * Fallback to null if no provider is set.
     *
     * @param string|null $provider
     * @return \Illuminate\Queue\Failed\FailedJobProviderInterface
     */
    public function provider($provider = null)
    {
        $config = $this->app['config']['queue.failed'];

        // Resolve provider with legacy configuration
        if (! isset($config['provider'])) {
            return $this->legacyProvider($config);
        }

        $provider = $provider ?? $config['provider'];
        $config = $config[$provider] ?? [];

        return isset($this->providers[$provider])
            ? $this->providers[$provider]($this->app, $config)
            : $this->providers['null']();
    }

    /**
     * Add a provider to providers list.
     *
     * @param string  $name
     * @param Closure $callback
     * @return void
     */
    public function addProvider($name, Closure $callback)
    {
        $this->providers[$name] = $callback;
    }

    /**
     * Register a new database failed job provider.
     *
     * @return Closure
     */
    protected function registerDatabaseFailedJobProvider()
    {
        $this->addProvider('database', function ($app, $config) {
            return new DatabaseFailedJobProvider(
                $app['db'],
                $config['connection'] ?? $config['database'],
                $config['table']
            );
        });
    }

    /**
     * Register null failed job provider.
     *
     * @return Closure
     */
    protected function registerNullFailedJobProvider()
    {
        $this->addProvider('null', function () {
            return new NullFailedJobProvider();
        });
    }

    /**
     * Resolve failed provider keeping backwards compatibility
     * with older configuration.
     *
     * @param array
     * @return \Illuminate\Queue\Failed\FailedJobProviderInterface
     */
    private function legacyProvider($config)
    {
        return isset($config['table'])
            ? $this->providers['database']($this->app, $config)
            : $this->providers['null']();
    }
}
