<?php

namespace Illuminate\Queue\Console;

use Illuminate\Console\Command;

class FlushFailedAttemptsCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'queue:flush-attempts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Flush all of the failed job attempts';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $this->laravel['queue.attempts.failer']->flush();

        $this->info('All failed job attempts were deleted successfully!');
    }
}
