<?php

namespace Illuminate\Foundation\Testing\Concerns;

use Closure;
use Illuminate\Foundation\Mix;
use Illuminate\Foundation\Vite;
use Illuminate\Support\Defer\DeferredCallbackCollection;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\HtmlString;
use Mockery;

trait InteractsWithContainer
{
    /**
     * The original Vite handler.
     *
     * @var \Illuminate\Foundation\Vite|null
     */
    protected $originalVite;

    /**
     * The original Laravel Mix handler.
     *
     * @var \Illuminate\Foundation\Mix|null
     */
    protected $originalMix;

    /**
     * The original deferred callbacks collection.
     *
     * @var \Illuminate\Support\Defer\DeferredCallbackCollection|null
     */
    protected $originalDeferredCallbacksCollection;

    /**
     * Register an instance of an object in the container.
     *
     * @param  string  $abstract
     * @param  object  $instance
     * @return object
     */
    protected function swap($abstract, $instance)
    {
        return $this->instance($abstract, $instance);
    }

    /**
     * Register an instance of an object in the container.
     *
     * @param  string  $abstract
     * @param  object  $instance
     * @return object
     */
    protected function instance($abstract, $instance)
    {
        $this->app->instance($abstract, $instance);

        return $instance;
    }

    /**
     * Mock an instance of an object in the container.
     *
     * @param  string  $abstract
     * @param  \Closure|null  $mock
     * @return \Mockery\MockInterface
     */
    protected function mock($abstract, ?Closure $mock = null)
    {
        return $this->instance($abstract, Mockery::mock(...array_filter(func_get_args())));
    }

    /**
     * Mock a partial instance of an object in the container.
     *
     * @param  string  $abstract
     * @param  \Closure|null  $mock
     * @return \Mockery\MockInterface
     */
    protected function partialMock($abstract, ?Closure $mock = null)
    {
        return $this->instance($abstract, Mockery::mock(...array_filter(func_get_args()))->makePartial());
    }

    /**
     * Spy an instance of an object in the container.
     *
     * @param  string  $abstract
     * @param  \Closure|null  $mock
     * @return \Mockery\MockInterface
     */
    protected function spy($abstract, ?Closure $mock = null)
    {
        return $this->instance($abstract, Mockery::spy(...array_filter(func_get_args())));
    }

    /**
     * Instruct the container to forget a previously mocked / spied instance of an object.
     *
     * @param  string  $abstract
     * @return $this
     */
    protected function forgetMock($abstract)
    {
        $this->app->forgetInstance($abstract);

        return $this;
    }

    /**
     * Register an empty handler for Vite in the container.
     *
     * @param  \Closure(string, string|null):void|null  $assetCallback
     * @return $this
     */
    protected function withoutVite($assetCallback = null)
    {
        if ($this->originalVite == null) {
            $this->originalVite = app(Vite::class);
        }

        Facade::clearResolvedInstance(Vite::class);

        $this->swap(Vite::class, new class($assetCallback) extends Vite
        {
            public function __construct(
                protected $assetCallback = null
            ) {
            }

            public function __invoke($entrypoints, $buildDirectory = null)
            {
                if ($this->assetCallback) {
                    foreach ((array) $entrypoints as $entrypoint) {
                        ($this->assetCallback)($entrypoint, $buildDirectory);
                    }
                }

                return new HtmlString('');
            }

            public function __call($method, $parameters)
            {
                return '';
            }

            public function __toString()
            {
                return '';
            }

            public function useIntegrityKey($key)
            {
                return $this;
            }

            public function useBuildDirectory($path)
            {
                return $this;
            }

            public function useHotFile($path)
            {
                return $this;
            }

            public function withEntryPoints($entryPoints)
            {
                return $this;
            }

            public function useScriptTagAttributes($attributes)
            {
                return $this;
            }

            public function useStyleTagAttributes($attributes)
            {
                return $this;
            }

            public function usePreloadTagAttributes($attributes)
            {
                return $this;
            }

            public function preloadedAssets()
            {
                return [];
            }

            public function reactRefresh()
            {
                return '';
            }

            public function content($asset, $buildDirectory = null)
            {
                if ($this->assetCallback) {
                    ($this->assetCallback)($asset, $buildDirectory);
                }

                return '';
            }

            public function asset($asset, $buildDirectory = null)
            {
                if ($this->assetCallback) {
                    ($this->assetCallback)($asset, $buildDirectory);
                }

                return '';
            }
        });

        return $this;
    }

    /**
     * Register an empty handler for Vite that validates asset existence.
     *
     * @return $this
     */
    protected function withoutViteStrict()
    {
        return $this->withoutVite(function ($asset) {
            $path = resource_path($asset);

            if (! file_exists($path)) {
                throw new \InvalidArgumentException("Vite asset does not exist: {$asset} (resolved to: {$path})");
            }
        });
    }

    /**
     * Restore Vite in the container.
     *
     * @return $this
     */
    protected function withVite()
    {
        if ($this->originalVite) {
            $this->app->instance(Vite::class, $this->originalVite);
        }

        return $this;
    }

    /**
     * Register an empty handler for Laravel Mix in the container.
     *
     * @param  \Closure|null  $assetCallback
     * @return $this
     */
    protected function withoutMix($assetCallback = null)
    {
        if ($this->originalMix == null) {
            $this->originalMix = app(Mix::class);
        }

        $this->swap(Mix::class, function ($asset = null) use ($assetCallback) {
            if ($assetCallback && $asset) {
                $assetCallback($asset, null);
            }

            return new HtmlString('');
        });

        return $this;
    }

    /**
     * Register an empty handler for Laravel Mix that validates asset existence.
     *
     * @return $this
     */
    protected function withoutMixStrict()
    {
        return $this->withoutMix(function ($asset, $buildDirectory) {
            $path = public_path($asset);

            if (! file_exists($path)) {
                throw new \InvalidArgumentException("Mix asset does not exist: {$asset} (resolved to: {$path})");
            }
        });
    }

    /**
     * Restore Laravel Mix in the container.
     *
     * @return $this
     */
    protected function withMix()
    {
        if ($this->originalMix) {
            $this->app->instance(Mix::class, $this->originalMix);
        }

        return $this;
    }

    /**
     * Execute deferred functions immediately.
     *
     * @return $this
     */
    protected function withoutDefer()
    {
        if ($this->originalDeferredCallbacksCollection == null) {
            $this->originalDeferredCallbacksCollection = $this->app->make(DeferredCallbackCollection::class);
        }

        $this->swap(DeferredCallbackCollection::class, new class extends DeferredCallbackCollection
        {
            public function offsetSet(mixed $offset, mixed $value): void
            {
                $value();
            }
        });

        return $this;
    }

    /**
     * Restore deferred functions.
     *
     * @return $this
     */
    protected function withDefer()
    {
        if ($this->originalDeferredCallbacksCollection) {
            $this->app->instance(DeferredCallbackCollection::class, $this->originalDeferredCallbacksCollection);
        }

        return $this;
    }
}
