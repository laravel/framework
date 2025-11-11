<?php

namespace Illuminate\Database;

use Illuminate\Container\Container;
use Illuminate\Contracts\Container\Container as ContainerContract;
use Illuminate\Contracts\Database\ConcurrencyErrorDetector as ConcurrencyErrorDetectorContract;
use Throwable;

trait DetectsConcurrencyErrors
{
    /**
     * The container instance.
     *
     * @var \Illuminate\Contracts\Container\Container|null
     */
    protected $concurrencyContainer;

    /**
     * Determine if the given exception was caused by a concurrency error such as a deadlock or serialization failure.
     *
     * @param  \Throwable  $e
     * @return bool
     */
    protected function causedByConcurrencyError(Throwable $e)
    {
        $container = $this->getConcurrencyContainer();

        $detector = $container->bound(ConcurrencyErrorDetectorContract::class)
            ? $container[ConcurrencyErrorDetectorContract::class]
            : new ConcurrencyErrorDetector();

        return $detector->causedByConcurrencyError($e);
    }

    /**
     * Get the container instance for concurrency detection.
     *
     * @return \Illuminate\Contracts\Container\Container
     */
    protected function getConcurrencyContainer(): ContainerContract
    {
        return $this->concurrencyContainer ?? Container::getInstance();
    }

    /**
     * Set the container instance for concurrency detection.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return $this
     */
    public function setConcurrencyContainer(ContainerContract $container)
    {
        $this->concurrencyContainer = $container;

        return $this;
    }
}
