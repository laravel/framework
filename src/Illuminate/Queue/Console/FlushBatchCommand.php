<?php

namespace Illuminate\Queue\Console;

use Carbon\Carbon;
use Illuminate\Bus\BatchRepository;
use Illuminate\Console\Command;

class FlushBatchCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'queue:flush-batch {--hours=24 : The number of hours to retain batch data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune stale entries from the batches database';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $count = 0;

        $repository = $this->laravel[BatchRepository::class];

        if (method_exists($repository, 'prune')) {
            $hours = $this->option('hours');

            $before = Carbon::now()->subHours($hours);

            $count = $repository->prune($before);
        }

        $this->info("{$count} entries deleted!");
    }
}
