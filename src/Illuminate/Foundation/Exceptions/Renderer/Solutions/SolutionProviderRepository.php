<?php

namespace Illuminate\Foundation\Exceptions\Renderer\Solutions;

use Illuminate\Contracts\Container\Container;
use Illuminate\Foundation\Exceptions\Renderer\Solutions\Contracts\ProvidesExceptionSolutions;
use Throwable;

class SolutionProviderRepository
{
    /** @var array<int, class-string<\Illuminate\Foundation\Exceptions\Renderer\Solutions\Contracts\SolutionProvider>> */
    protected array $providers = [];

    /**
     * Create a new solution provider repository instance.
     */
    public function __construct(
        protected Container $container,
    ) {
    }

    /**
     * Register one or more solution providers.
     *
     * @param  array<int, class-string<\Illuminate\Foundation\Exceptions\Renderer\Solutions\Contracts\SolutionProvider>>  $providers
     */
    public function register(array $providers): static
    {
        $this->providers = array_merge($this->providers, $providers);

        return $this;
    }

    /**
     * Get the registered solution providers.
     *
     * @return array<int, class-string<\Illuminate\Foundation\Exceptions\Renderer\Solutions\Contracts\SolutionProvider>>
     */
    public function getProviders(): array
    {
        return $this->providers;
    }

    /**
     * Get all solutions for the given throwable (walks the full exception chain).
     *
     * @return array<int, \Illuminate\Foundation\Exceptions\Renderer\Solutions\Contracts\Solution>
     */
    public function getSolutions(Throwable $throwable): array
    {
        $solutions = [];

        $current = $throwable;

        while ($current !== null) {
            if ($current instanceof ProvidesExceptionSolutions) {
                $solutions = array_merge($solutions, $current->getSolutions());
            }

            foreach ($this->providers as $providerClass) {
                $provider = $this->container->make($providerClass);

                if ($provider->canSolve($current)) {
                    $solutions = array_merge($solutions, $provider->getSolutions($current));
                }
            }

            $current = $current->getPrevious();
        }

        return $solutions;
    }
}
