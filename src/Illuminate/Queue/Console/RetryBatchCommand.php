<?php

namespace Illuminate\Queue\Console;

use Illuminate\Bus\BatchRepository;
use Illuminate\Console\Command;

class RetryBatchCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'queue:retry-batch {id : The ID of the batch whose failed jobs should be retried}';

    /**
     * The name of the console command.
     *
     * This name is used to identify the command during lazy loading.
     *
     * @var string|null
     *
     * @deprecated
     */
    protected static $defaultName = 'queue:retry-batch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retry the failed jobs for a batch';

    /**
     * Execute the console command.
     *
     * @return int|null
     */
    public function handle()
    {
        $batch = $this->laravel[BatchRepository::class]->find($id = $this->argument('id'));

        if (! $batch) {
            $this->error("Unable to find a batch with ID [{$id}].");

            return 1;
        } elseif (empty($batch->failedJobIds)) {
            $this->error('The given batch does not contain any failed jobs.');

            return 1;
        }

        foreach ($batch->failedJobIds as $failedJobId) {
            $this->call('queue:retry', ['id' => $failedJobId]);
        }
    }
}
