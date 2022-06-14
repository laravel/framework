<?php

namespace Illuminate\Foundation\Support\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * @mixin ServiceProvider
 *
 * @property array<class-string, class-string> $policies - Get the policies defined on the provider.
 */
trait WithPolicies
{
    /**
     * Register the application's policies.
     *
     * @return void
     */
    public function registerWithPolicies()
    {
        foreach ($this->policies() as $key => $value) {
            Gate::policy($key, $value);
        }
    }

    /**
     * Get the policies defined on the provider.
     *
     * @return array<class-string, class-string>
     */
    public function policies()
    {
        return $this->policies ?? [];
    }
}
