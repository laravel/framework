<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Foundation\Events\DeploymentFinished;
use Illuminate\Support\Facades\Event;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'deploy:finish')]
class DeploymentFinishCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'deploy:finish';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Finish a deployment and restart long-running processes';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Event::dispatch(new DeploymentFinished);

        $this->components->info('DeploymentFinished event has been broadcast.');

        return 0;
    }
}
