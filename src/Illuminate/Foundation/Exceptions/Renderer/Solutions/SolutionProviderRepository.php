<?php

namespace Illuminate\Foundation\Exceptions\Renderer\Solutions;

use Illuminate\Foundation\Exceptions\Renderer\Solutions\Contracts\HasSolutions;
use Throwable;

class SolutionProviderRepository
{
    /** @var array<int, class-string<\Illuminate\Foundation\Exceptions\Renderer\Solutions\Contracts\SolutionProvider>> */
    private array $providers = [];

    /**
     * @param  array<int, class-string<\Illuminate\Foundation\Exceptions\Renderer\Solutions\Contracts\SolutionProvider>>  $providers
     */
    public function register(array $providers): static
    {
        $this->providers = array_merge($this->providers, $providers);

        return $this;
    }

    /**
     * @return array<int, \Illuminate\Foundation\Exceptions\Renderer\Solutions\Contracts\Solution>
     */
    public function getSolutions(Throwable $throwable): array
    {
        $solutions = [];

        $current = $throwable;
        while ($current !== null) {
            if ($current instanceof HasSolutions) {
                $solutions = array_merge($solutions, $current->getSolutions());
            }

            foreach ($this->providers as $providerClass) {
                /** @var \Illuminate\Foundation\Exceptions\Renderer\Solutions\Contracts\SolutionProvider $provider */
                $provider = app($providerClass);

                if ($provider->canSolve($current)) {
                    $solutions = array_merge($solutions, $provider->getSolutions($current));
                }
            }

            $current = $current->getPrevious();
        }

        return $solutions;
    }
}
