<?php

namespace Illuminate\Queue\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'queue:failed')]
class ListFailedCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'queue:failed
                            {--json : Output the failed jobs as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all of the failed queue jobs';

    /**
     * The table headers for the command.
     *
     * @var string[]
     */
    protected $headers = ['ID', 'Connection', 'Queue', 'Class', 'Failed At'];

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $jobs = $this->getFailedJobs();

        if ($this->option('json')) {
            return $this->displayFailedJobsAsJson($jobs);
        }

        if (count($jobs) === 0) {
            return $this->components->info('No failed jobs found.');
        }

        $this->newLine();
        $this->displayFailedJobs($jobs);
        $this->newLine();
    }

    /**
     * Compile the failed jobs into a displayable format.
     *
     * @return array
     */
    protected function getFailedJobs()
    {
        $failed = $this->laravel['queue.failer']->all();

        return (new Collection($failed))
            ->map(fn ($failed) => $this->parseFailedJob((array) $failed))
            ->filter()
            ->all();
    }

    /**
     * Parse the failed job row.
     *
     * @param  array  $failed
     * @return array
     */
    protected function parseFailedJob(array $failed)
    {
        $row = array_values(Arr::except($failed, ['payload', 'exception']));

        array_splice($row, 3, 0, $this->extractJobName($failed['payload']) ?: '');

        return $row;
    }

    /**
     * Extract the failed job name from payload.
     *
     * @param  string  $payload
     * @return string|null
     */
    private function extractJobName($payload)
    {
        $payload = json_decode($payload, true);

        if (! $payload) {
            return null;
        }

        if (! isset($payload['data']['command'])) {
            return $payload['job'] ?? null;
        }

        if (! empty($payload['displayName']) && is_string($payload['displayName'])) {
            return $payload['displayName'];
        }

        return $this->matchJobName($payload);
    }

    /**
     * Match the job name from the payload.
     *
     * @param  array  $payload
     * @return string|null
     */
    protected function matchJobName($payload)
    {
        preg_match('/"([^"]+)"/', $payload['data']['command'], $matches);

        return $matches[1] ?? $payload['job'] ?? null;
    }

    /**
     * Display the failed jobs in the console.
     *
     * @param  array  $jobs
     * @return void
     */
    protected function displayFailedJobs(array $jobs)
    {
        (new Collection($jobs))->each(
            fn ($job) => $this->components->twoColumnDetail(
                sprintf('<fg=gray>%s</> %s</>', $job[4], $job[0]),
                sprintf('<fg=gray>%s@%s</> %s', $job[1], $job[2], $job[3])
            ),
        );
    }

    /**
     * Display the failed jobs as JSON.
     *
     * @param  array  $jobs
     * @return void
     */
    protected function displayFailedJobsAsJson(array $jobs)
    {
        $this->output->writeln((new Collection($jobs))->values()->map(fn ($job) => [
            'id' => $job[0],
            'connection' => $job[1],
            'queue' => $job[2],
            'class' => $job[3],
            'failed_at' => $job[4],
        ])->toJson());
    }
}
