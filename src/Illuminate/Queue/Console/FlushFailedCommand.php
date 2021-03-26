<?php

namespace Illuminate\Queue\Console;

use Illuminate\Console\Command;

class FlushFailedCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'queue:flush {--age= : Only clear failed jobs older than the given age in days}';

    /**
     * The name of the console command.
     *
     * This name is used to identify the command during lazy loading.
     *
     * @var string|null
     */
    protected static $defaultName = 'queue:flush';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Flush all of the failed queue jobs';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->laravel['queue.failer']->flush($this->option('age'));

        if ($this->option('age')) {
            $this->info("Failed jobs older than {$this->option('age')} days deleted successfully!");

            return;
        }

        $this->info('All failed jobs deleted successfully!');
    }
}
