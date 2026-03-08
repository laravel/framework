<?php

namespace Illuminate\Console\Scheduling;

use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskSkipped;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Date;

class ScheduleFailureLogger
{
    /**
     * Create a new failure logger instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  non-negative-int  $maxEntries
     */
    public function __construct(
        protected Filesystem $files,
        protected int $maxEntries = 1000,
    ) {
    }

    /**
     * Handle a scheduled task failure event.
     *
     * @param  \Illuminate\Console\Events\ScheduledTaskFailed  $event
     * @return void
     */
    public function handleTaskFailed(ScheduledTaskFailed $event)
    {
        $this->writeEntry([
            'timestamp' => Date::now()->toIso8601String(),
            'command' => $event->task->command ?? $event->task->getSummaryForDisplay(),
            'description' => $event->task->description ?? '',
            'type' => 'failed',
            'exit_code' => $event->task->exitCode,
            'exception' => $event->exception::class.': '.$event->exception->getMessage(),
            'mutex' => $event->task->mutexName(),
        ]);
    }

    /**
     * Handle a scheduled task skipped event.
     *
     * @param  \Illuminate\Console\Events\ScheduledTaskSkipped  $event
     * @return void
     */
    public function handleTaskSkipped(ScheduledTaskSkipped $event)
    {
        $this->writeEntry([
            'timestamp' => Date::now()->toIso8601String(),
            'command' => $event->task->command ?? $event->task->getSummaryForDisplay(),
            'description' => $event->task->description ?? '',
            'type' => 'skipped',
            'mutex' => $event->task->mutexName(),
        ]);
    }

    /**
     * Write an entry to the failure log.
     *
     * @param  array{
     *     timestamp: string,
     *     command: string,
     *     description: string,
     *     type: 'failed'|'skipped',
     *     exit_code?: int|null,
     *     exception?: string,
     *     mutex: string,
     * }  $entry
     * @return void
     */
    protected function writeEntry(array $entry)
    {
        $path = static::logPath();

        $this->files->ensureDirectoryExists(dirname($path));

        $this->files->append($path, json_encode($entry)."\n");

        $this->rotateIfNeeded($path);
    }

    /**
     * Rotate the log file if it exceeds the max entries.
     *
     * @param  string  $path
     * @return void
     */
    protected function rotateIfNeeded(string $path)
    {
        if (! $this->files->exists($path)) {
            return;
        }

        $lines = array_filter(explode("\n", $this->files->get($path)));

        if (count($lines) > $this->maxEntries) {
            $lines = array_slice($lines, -$this->maxEntries);

            $this->files->put($path, implode("\n", $lines)."\n");
        }
    }

    /**
     * Get the path to the failure log file.
     *
     * @return string
     */
    public static function logPath()
    {
        return storage_path('logs/schedule-failures.json');
    }
}
