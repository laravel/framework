<?php

namespace Illuminate\Console\Scheduling;

use Closure;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionFunction;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'schedule:why-not')]
class ScheduleWhyNotCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'schedule:why-not
        {--json : Output the results as JSON}
        {--event= : Filter to a specific command or description}
        {--limit=1 : Number of failure entries to show per event}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnose why scheduled tasks are failing or not running';

    /**
     * Execute the console command.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return void
     */
    public function handle(Schedule $schedule, Filesystem $files)
    {
        $events = new Collection($schedule->events());

        if ($events->isEmpty()) {
            if ($this->option('json')) {
                $this->output->writeln('[]');
            } else {
                $this->components->info('No scheduled tasks have been defined.');
            }

            return;
        }

        $filter = $this->option('event');

        if ($filter) {
            $events = $events->filter(function ($event) use ($filter) {
                $command = $this->getCommandName($event);

                return str_contains($command, $filter)
                    || str_contains($event->description ?? '', $filter);
            });
        }

        $failures = $this->readFailureLog($files);

        $rows = $events->map(function ($event) use ($failures) {
            return $this->buildRow($event, $failures);
        });

        $this->option('json')
            ? $this->displayJson($rows)
            : $this->displayTable($rows);
    }

    /**
     * Build a row of diagnostic data for the given event.
     *
     * @param  \Illuminate\Console\Scheduling\Event  $event
     * @param  \Illuminate\Support\Collection<int, array{
     *     mutex?: string,
     *     command?: string,
     *     type?: string,
     *     exception?: string,
     *     reason?: string,
     *     timestamp?: string,
     * }>  $failures
     * @return array{
     *     command: string,
     *     status: 'OK'|'FAILED'|'SKIPPED',
     *     diagnostics: string,
     *     last_failure: string,
     *     last_failed_at: string,
     * }
     */
    protected function buildRow(Event $event, Collection $failures)
    {
        $command = $this->getCommandName($event);
        $mutexName = $event->mutexName();
        $diagnostics = $this->getDiagnostics($event);

        $limit = (int) $this->option('limit');

        $eventFailures = $failures->filter(function ($entry) use ($mutexName, $command) {
            return ($entry['mutex'] ?? '') === $mutexName
                || str_contains($entry['command'] ?? '', $command);
        })->take(-$limit)->values();

        $lastFailure = $eventFailures->last();

        $status = 'OK';
        if ($lastFailure) {
            $status = strtoupper($lastFailure['type'] ?? 'failed');
        }

        return [
            'command' => $command,
            'status' => $status,
            'diagnostics' => $diagnostics,
            'last_failure' => $lastFailure['exception'] ?? $lastFailure['reason'] ?? '—',
            'last_failed_at' => $lastFailure['timestamp'] ?? '—',
        ];
    }

    /**
     * Get the real-time diagnostics for an event.
     *
     * @param  \Illuminate\Console\Scheduling\Event  $event
     * @return string
     */
    protected function getDiagnostics(Event $event)
    {
        $issues = [];

        if (! $event->runsInEnvironment($this->laravel->environment())) {
            $issues[] = 'Wrong environment';
        }

        if (! $event->runsInMaintenanceMode() && $this->laravel->isDownForMaintenance()) {
            $issues[] = 'In maintenance';
        }

        if (! $event->filtersPass($this->laravel)) {
            $issues[] = 'Filters failing';
        }

        if (! $event->expressionPasses()) {
            $issues[] = 'Not due';
        } else {
            $issues[] = 'Due';
        }

        if ($event->mutex->exists($event)) {
            $issues[] = 'Mutex active';
        } else {
            $issues[] = 'No mutex';
        }

        return implode(', ', $issues);
    }

    /**
     * Get the display name for the command.
     *
     * @param  \Illuminate\Console\Scheduling\Event  $event
     * @return string
     */
    protected function getCommandName(Event $event)
    {
        if ($event instanceof CallbackEvent) {
            $summary = $event->getSummaryForDisplay();

            if (in_array($summary, ['Closure', 'Callback'])) {
                return 'Closure at: '.$this->getClosureLocation($event);
            }

            return $summary;
        }

        return $event->normalizeCommand($event->command ?? '');
    }

    /**
     * Get the file and line number for the event closure.
     *
     * @param  \Illuminate\Console\Scheduling\CallbackEvent  $event
     * @return string
     */
    private function getClosureLocation(CallbackEvent $event)
    {
        $callback = (new ReflectionClass($event))->getProperty('callback')->getValue($event);

        if ($callback instanceof Closure) {
            $function = new ReflectionFunction($callback);

            return sprintf(
                '%s:%s',
                str_replace($this->laravel->basePath().DIRECTORY_SEPARATOR, '', $function->getFileName() ?: ''),
                $function->getStartLine()
            );
        }

        if (is_string($callback)) {
            return $callback;
        }

        if (is_array($callback)) {
            $className = is_string($callback[0]) ? $callback[0] : $callback[0]::class;

            return sprintf('%s::%s', $className, $callback[1]);
        }

        return sprintf('%s::__invoke', $callback::class);
    }

    /**
     * Read and parse the failure log.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return \Illuminate\Support\Collection
     */
    protected function readFailureLog(Filesystem $files)
    {
        $path = ScheduleFailureLogger::logPath();

        if (! $files->exists($path)) {
            return new Collection;
        }

        $lines = array_filter(explode("\n", $files->get($path)));

        return (new Collection($lines))
            ->map(fn ($line) => json_decode($line, true))
            ->filter()
            ->values();
    }

    /**
     * Display the results as a table.
     *
     * @param  \Illuminate\Support\Collection  $rows
     * @return void
     */
    protected function displayTable(Collection $rows)
    {
        $this->table(
            ['Command', 'Status', 'Diagnostics', 'Last Failure', 'Last Failed At'],
            $rows->map(function ($row) {
                return [
                    $row['command'],
                    $this->formatStatus($row['status']),
                    $row['diagnostics'],
                    $this->truncate($row['last_failure'], 50),
                    $row['last_failed_at'],
                ];
            })->all()
        );
    }

    /**
     * Display the results as JSON.
     *
     * @param  \Illuminate\Support\Collection  $rows
     * @return void
     */
    protected function displayJson(Collection $rows)
    {
        $this->output->writeln($rows->values()->toJson(JSON_PRETTY_PRINT));
    }

    /**
     * Format the status for display.
     *
     * @param  string  $status
     * @return string
     */
    protected function formatStatus(string $status)
    {
        return match ($status) {
            'OK' => '<fg=green>OK</>',
            'FAILED' => '<fg=red>FAILED</>',
            'SKIPPED' => '<fg=yellow>SKIPPED</>',
            default => $status,
        };
    }

    /**
     * Truncate a string.
     *
     * @param  string  $value
     * @param  int  $length
     * @return string
     */
    protected function truncate(string $value, int $length)
    {
        if (mb_strlen($value) <= $length) {
            return $value;
        }

        return mb_substr($value, 0, $length - 1).'…';
    }
}
