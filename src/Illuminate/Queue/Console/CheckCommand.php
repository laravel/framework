<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class CheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check if the queue worker process is running and start it if not.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $workerName = 'queue:work';

        $isRunning = $this->isProcessRunning($workerName);

        if (! $isRunning) {
            Log::info('Queue worker process is not running. Starting the worker...');

            Artisan::call('queue:work', ['--daemon' => true]);
        } else {
            Log::info('Queue worker process is already running.');
        }
    }

    /**
     * @param  string  $processName
     * @return bool
     */
    private function isProcessRunning(string $processName): bool
    {
        $processes = shell_exec('ps aux | grep "'.$processName.'" | grep -v grep');

        return (bool) $processes;
    }
}
