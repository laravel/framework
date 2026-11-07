<?php

namespace Illuminate\Queue\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'queue:forget')]
class ForgetFailedCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'queue:forget {id* : The IDs of the failed jobs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete a failed queue job';

    /**
     * Execute the console command.
     *
     * @return int|null
     */
    public function handle()
    {
        $missing = false;

        foreach ((array) $this->argument('id') as $id) {
            if ($this->laravel['queue.failer']->forget($id)) {
                $this->components->info("Failed job [{$id}] deleted successfully.");
            } else {
                $this->components->error("No failed job matches the ID [{$id}].");

                $missing = true;
            }
        }

        if ($missing) {
            return self::FAILURE;
        }
    }
}
