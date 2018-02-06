<?php

namespace Illuminate\Queue;

use Closure;
use Illuminate\Support\ProcessUtils;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\PhpExecutableFinder;

class Pool
{
    /**
     * The command working path.
     *
     * @var string
     */
    protected $commandPath;

    /**
     * The environment the workers should run under.
     *
     * @var string
     */
    protected $environment;

    /**
     * The worker processes that started.
     *
     * @var array
     */
    protected $processes;

    /**
     * The amount of seconds to wait before polling the queue.
     *
     * @var int
     */
    protected $sleep = 3;

    /**
     * The amount of times to try a job before logging it failed.
     *
     * @var int
     */
    protected $maxTries = 0;

    /**
     * The queue worker command line.
     *
     * @var string
     */
    protected $workerCommand;

    /**
     * The output handler callback.
     *
     * @var \Closure|null
     */
    protected $outputHandler;

    /**
     * Create a new queue listener.
     *
     * @param  string  $commandPath
     * @return void
     */
    public function __construct($commandPath)
    {
        $this->commandPath = $commandPath;
        $this->workerCommand = $this->buildCommandTemplate();
    }

    /**
     * Build the environment specific worker command.
     *
     * @return string
     */
    protected function buildCommandTemplate()
    {
        $command = 'queue:work %s --queue=%s --delay=%s --memory=%s --sleep=%s --tries=%s';

        return "{$this->phpBinary()} {$this->artisanBinary()} {$command}";
    }

    /**
     * Get the PHP binary.
     *
     * @return string
     */
    protected function phpBinary()
    {
        return ProcessUtils::escapeArgument(
            (new PhpExecutableFinder)->find(false)
        );
    }

    /**
     * Get the Artisan binary.
     *
     * @return string
     */
    protected function artisanBinary()
    {
        return defined('ARTISAN_BINARY')
            ? ProcessUtils::escapeArgument(ARTISAN_BINARY)
            : 'artisan';
    }

    /**
     * Start workers.
     *
     * @param  string  $connection
     * @param  string  $queue
     * @param  \Illuminate\Queue\PoolOption  $options
     * @return void
     */
    public function start($connection, $queue, PoolOption $options)
    {
        $processes = $this->makeProcesses($connection, $queue, $options);

        $this->setProcesses($processes);

        while (true) {
            $this->runProcesses($options->memory);
        }
    }

    /**
     * Create an array of Symfony processes.
     *
     * @param $connection
     * @param $queue
     * @param \Illuminate\Queue\PoolOption $options
     * @return array
     */
    public function makeProcesses($connection, $queue, PoolOption $options)
    {
        $processes = [];

        foreach(range(1, $options->workers) as $key) {
            $processes[$key] = $this->makeProcess($connection, $queue, $options);
        }

        return $processes;
    }

    /**
     * Create a new Symfony process for the worker.
     *
     * @param  string  $connection
     * @param  string  $queue
     * @param  \Illuminate\Queue\PoolOption  $options
     * @return \Symfony\Component\Process\Process
     */
    public function makeProcess($connection, $queue, PoolOption $options)
    {
        $command = $this->workerCommand;

        // If the environment is set, we will append it to the command string so the
        // workers will run under the specified environment. Otherwise, they will
        // just run under the production environment which is not always right.
        if (isset($options->environment)) {
            $command = $this->addEnvironment($command, $options);
        }

        // Next, we will just format out the worker commands with all of the various
        // options available for the command. This will produce the final command
        // line that we will pass into a Symfony process object for processing.
        $command = $this->formatCommand(
            $command, $connection, $queue, $options
        );

        return new Process(
            $command, $this->commandPath, null, null, $options->timeout
        );
    }

    /**
     * Add the environment option to the given command.
     *
     * @param  string  $command
     * @param  \Illuminate\Queue\PoolOption  $options
     * @return string
     */
    protected function addEnvironment($command, PoolOption $options)
    {
        return $command.' --env='.ProcessUtils::escapeArgument($options->environment);
    }

    /**
     * Format the given command with the listener options.
     *
     * @param  string  $command
     * @param  string  $connection
     * @param  string  $queue
     * @param  \Illuminate\Queue\PoolOption  $options
     * @return string
     */
    protected function formatCommand($command, $connection, $queue, PoolOption $options)
    {
        return sprintf(
            $command,
            ProcessUtils::escapeArgument($connection),
            ProcessUtils::escapeArgument($queue),
            $options->delay, $options->memory,
            $options->sleep, $options->maxTries
        );
    }

    /**
     * Run worker processes.
     *
     * @param  int  $memory
     * @return void
     */
    public function runProcesses($memory)
    {
        $processes = $this->getProcesses();

        array_walk( $processes, function ($process, $key) {
            if (!$process->isRunning()) {
                $process->start(function ($type, $line) use ($key) {
                    $line = "[Worker $key]: $line";
                    $this->handleWorkerOutput($type, $line);
                });
            }
        });

        // Once we have run the job we'll go check if the memory limit has been exceeded
        // for the script. If it has, we will kill this script so the process manager
        // will restart this with a clean slate of memory automatically on exiting.
        if ($this->memoryExceeded($memory)) {
            $this->stop();
        }
    }

    /**
     * Get processes.
     *
     * @return array
     */
    public function getProcesses()
    {
        return $this->processes;
    }

    /**
     * Set processes.
     *
     * @param array $processes
     */
    public function setProcesses($processes)
    {
        $this->processes = $processes;
    }

    /**
     * Handle output from the worker process.
     *
     * @param  int  $type
     * @param  string  $line
     * @return void
     */
    protected function handleWorkerOutput($type, $line)
    {
        if (isset($this->outputHandler)) {
            call_user_func($this->outputHandler, $type, $line);
        }
    }

    /**
     * Determine if the memory limit has been exceeded.
     *
     * @param  int  $memoryLimit
     * @return bool
     */
    public function memoryExceeded($memoryLimit)
    {
        return (memory_get_usage() / 1024 / 1024) >= $memoryLimit;
    }

    /**
     * Stop listening and bail out of the script.
     *
     * @return void
     */
    public function stop()
    {
        die;
    }

    /**
     * Set the output handler callback.
     *
     * @param  \Closure  $outputHandler
     * @return void
     */
    public function setOutputHandler(Closure $outputHandler)
    {
        $this->outputHandler = $outputHandler;
    }
}
