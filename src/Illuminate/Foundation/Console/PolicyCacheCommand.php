<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'policy:cache',
    description: "Discover and cache the application's policies.",
)]
class PolicyCacheCommand extends Command
{
    public function handle(): void
    {
        $this->callSilent('policy:clear');

        file_put_contents(
            $this->laravel->getCachedPoliciesPath(),
            '<?php return '.var_export($this->getPolicies(), true).';',
        );

        $this->components->info('Policies cached successfully.');
    }

    /**
     * Get all of the policies configured for the application.
     *
     * @return array<class-string, class-string>
     */
    protected function getPolicies(): array
    {
        $policies = [];

        foreach ($this->laravel->getProviders(AuthServiceProvider::class) as $provider) {
            $policies[$provider::class] = [
                ...$provider->discoveredPolicies(),
                ...$provider->policies(),
            ];
        }

        return $policies;
    }
}
