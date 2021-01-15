<?php

namespace Illuminate\Queue\Console;

use Carbon\Carbon;
use Illuminate\Bus\BatchRepository;
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
     * The name of the console command.
     *
     * This name is used to identify the command during lazy loading.
     *
     * @var string|null
     */
    protected static $defaultName = 'queue:prune-batches';

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

        if ($repository instanceof PrunableBatchRepository) {
            $count = $repository->prune(Carbon::now()->subHours($this->option('hours')));
        }

        $this->info("{$count} entries deleted!");
    }
}
