<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'policy:clear',
    description: 'Clear all cached policies.',
)]
class PolicyClearCommand extends Command
{
    public function __construct(protected Filesystem $files)
    {
        parent::__construct();
    }

    /** @throws \RuntimeException */
    public function handle(): void
    {
        $this->files->delete($this->laravel->getCachedPoliciesPath());

        $this->components->info('Cached policies cleared successfully.');
    }
}
