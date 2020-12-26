<?php

namespace Illuminate\Queue\Console;

use Carbon\Carbon;
use Illuminate\Bus\PrunableBatchRepository;
use Illuminate\Console\Command;

class PruneBatchesCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'queue:prune-batches {--hours=24 : The number of hours to retain batch data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune stale entries from the batches database';

    /**
     * Execute the console command.
     *
     * @param  PrunableBatchRepository $repository
     * @return void
     */
    public function handle(PrunableBatchRepository $repository)
    {
        $count = $repository->prune(
            Carbon::now()->subHours($this->option('hours'))
        );

        $this->info("{$count} entries deleted!");
    }
}
