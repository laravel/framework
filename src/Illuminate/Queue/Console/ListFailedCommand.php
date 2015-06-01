<?php

namespace Illuminate\Queue\Console;

use Illuminate\Console\Command;

class ListFailedCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'queue:failed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all of the failed queue jobs';

    /**
     * The table headers for the command.
     *
     * @var array
     */
    protected $headers = ['ID', 'Connection', 'Queue', 'Class', 'Failed At'];

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $jobs = $this->getFailedJobs();

        if (count($jobs) == 0) {
            return $this->info('No failed jobs!');
        }

        $this->displayFailedJobs($jobs);
    }

    /**
     * Compile the failed jobs into a displayable format.
     *
     * @return array
     */
    protected function getFailedJobs()
    {
        $results = [];

        foreach ($this->laravel['queue.failer']->all() as $failed) {
            $results[] = $this->parseFailedJob((array) $failed);
        }

        return array_filter($results);
    }

    /**
     * Parse the failed job row.
     *
     * @param  array  $failed
     * @return array
     */
    protected function parseFailedJob(array $failed)
    {
        $row = array_values(array_except($failed, ['payload']));

        array_splice($row, 3, 0, array_get(json_decode($failed['payload'], true), 'job'));

        return $row;
    }

    /**
     * Display the failed jobs in the console.
     *
     * @param  array  $jobs
     * @return void
     */
    protected function displayFailedJobs(array $jobs)
    {
        $this->table($this->headers, $jobs);
    }
}
