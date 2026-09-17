<?php

namespace Illuminate\Queue\Console;

use Illuminate\Console\Command;
use Illuminate\Queue\Listener;
use Illuminate\Queue\ListenerOptions;
use Illuminate\Support\Stringable;
use InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

use function Illuminate\Support\artisan_binary;
use function Illuminate\Support\php_binary;

#[AsCommand(name: 'queue:listen')]
class ListenCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'queue:listen
                            {connection? : The name of connection}
                            {--name=default : The name of the worker}
                            {--delay=0 : The number of seconds to delay failed jobs (Deprecated)}
                            {--backoff=0 : The number of seconds to wait before retrying a job that encountered an uncaught exception}
                            {--force : Force the worker to run even in maintenance mode}
                            {--memory=128 : The memory limit in megabytes}
                            {--queue= : The queue to listen on}
                            {--watch : Restart a persistent worker when files change}
                            {--poll : Use polling for file watching with --watch}
                            {--sleep=3 : The number of seconds to sleep when no job is available}
                            {--rest=0 : The number of seconds to rest between jobs}
                            {--timeout=60 : The number of seconds a child process can run}
                            {--tries=1 : The number of times to attempt a job before logging it failed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Listen to a given queue';

    /**
     * The queue listener instance.
     *
     * @var \Illuminate\Queue\Listener
     */
    protected $listener;

    /**
     * The queue worker process instance.
     *
     * @var \Symfony\Component\Process\Process|null
     */
    protected $workerProcess;

    /**
     * The file watcher process instance.
     *
     * @var \Symfony\Component\Process\Process|null
     */
    protected $watcherProcess;

    /**
     * Indicates if a termination signal has been received.
     *
     * @var int|null
     */
    protected $trappedSignal = null;

    /**
     * Create a new queue listen command.
     *
     * @param  \Illuminate\Queue\Listener  $listener
     */
    public function __construct(Listener $listener)
    {
        parent::__construct();

        $this->setOutputHandler($this->listener = $listener);
    }

    /**
     * Execute the console command.
     *
     * @return int|null
     */
    public function handle()
    {
        if ($this->option('watch')) {
            return $this->watch();
        }

        // We need to get the right queue for the connection which is set in the queue
        // configuration file for the application. We will pull it based on the set
        // connection being run for the queue operation currently being executed.
        $queue = $this->getQueue(
            $connection = $this->input->getArgument('connection')
        );

        $this->components->info(sprintf('Processing jobs from the [%s] %s.', $queue, (new Stringable('queue'))->plural(explode(',', $queue))));

        $this->listener->listen(
            $connection, $queue, $this->gatherOptions()
        );
    }

    /**
     * Run a persistent worker and restart it when files change.
     *
     * @return int
     */
    protected function watch()
    {
        $this->components->info('Starting queue worker and watching for file changes...');

        $this->watcherProcess = $this->startWatcher();

        if ($this->watcherProcess->isTerminated()) {
            return $this->watcherFailed();
        }

        if (! $this->startWorker()) {
            return Command::FAILURE;
        }

        $this->listenForChanges();

        return Command::SUCCESS;
    }

    /**
     * Start the file watcher process.
     *
     * @return \Symfony\Component\Process\Process
     */
    protected function startWatcher()
    {
        if (empty($paths = $this->laravel['config']->get('queue.watch'))) {
            throw new InvalidArgumentException(
                'List of directories / files to watch not found. Please update your "config/queue.php" configuration file.',
            );
        }

        $nodeExecutable = (new ExecutableFinder)->find('node');

        if (! $nodeExecutable) {
            throw new InvalidArgumentException(
                'Node could not be found. Please ensure Node is installed and available in your system PATH.',
            );
        }

        $process = new Process([
            $nodeExecutable,
            'file-watcher.cjs',
            json_encode(collect($paths)->map(fn ($path) => $this->laravel->basePath($path))->values()->all()),
            $this->option('poll') ? '1' : '',
        ], __DIR__.'/../resources', ['NODE_PATH' => $this->laravel->basePath('node_modules')], null, null);

        $process->start();

        sleep(1);

        return $process;
    }

    /**
     * Start the queue worker process.
     *
     * @return bool
     */
    protected function startWorker()
    {
        $this->workerProcess = $this->createWorkerProcess();

        $this->trap(fn () => [SIGINT, SIGTERM, SIGQUIT], function ($signal) {
            $this->trappedSignal = $signal;

            $this->workerProcess->stop(signal: $signal);
            $this->workerProcess->wait();

            if ($this->watcherProcess) {
                $this->watcherProcess->stop();
            }
        });

        $this->workerProcess->start();

        usleep(100000);

        return ! $this->workerProcess->isTerminated();
    }

    /**
     * Listen for file changes and restart the worker when detected.
     *
     * @return void
     */
    protected function listenForChanges()
    {
        while (! $this->trappedSignal) {
            if ($this->watcherProcess->getIncrementalOutput()) {
                $this->restartWorker();
            }

            $this->output->write($this->workerProcess->getIncrementalOutput());

            if (! $this->workerProcess->isRunning()) {
                break;
            }

            usleep(500000);
        }
    }

    /**
     * Restart the queue worker process.
     *
     * @return void
     */
    protected function restartWorker()
    {
        $this->components->info('File changed. Restarting queue worker...');

        $this->workerProcess->stop();
        $this->workerProcess->wait();

        $this->startWorker();
    }

    /**
     * Create the persistent queue worker process.
     *
     * @return \Symfony\Component\Process\Process
     */
    protected function createWorkerProcess()
    {
        $command = [php_binary(), artisan_binary(), 'queue:work'];

        if (! is_null($connection = $this->argument('connection'))) {
            $command[] = $connection;
        }

        foreach (['name', 'backoff', 'memory', 'queue', 'sleep', 'rest', 'timeout', 'tries', 'env'] as $option) {
            if (! is_null($value = $this->option($option))) {
                $command[] = "--{$option}={$value}";
            }
        }

        if ($this->option('force')) {
            $command[] = '--force';
        }

        return new Process($command, $this->laravel->basePath(), null, null, null);
    }

    /**
     * Report a failed file watcher.
     *
     * @return int
     */
    protected function watcherFailed()
    {
        $this->components->error(
            'Unable to start file watcher. Please ensure Node.js and the chokidar npm package are installed.',
        );

        $this->output->writeln($this->watcherProcess->getErrorOutput());

        return Command::FAILURE;
    }

    /**
     * Get the name of the queue connection to listen on.
     *
     * @param  string  $connection
     * @return string
     */
    protected function getQueue($connection)
    {
        $connection = $connection ?: $this->laravel['config']['queue.default'];

        return $this->input->getOption('queue') ?: $this->laravel['config']->get(
            "queue.connections.{$connection}.queue", 'default'
        );
    }

    /**
     * Get the listener options for the command.
     *
     * @return \Illuminate\Queue\ListenerOptions
     */
    protected function gatherOptions()
    {
        $backoff = $this->hasOption('backoff')
            ? $this->option('backoff')
            : $this->option('delay');

        return new ListenerOptions(
            name: $this->option('name'),
            environment: $this->option('env'),
            backoff: $backoff,
            memory: $this->option('memory'),
            timeout: $this->option('timeout'),
            sleep: $this->option('sleep'),
            maxTries: $this->option('tries'),
            force: $this->option('force'),
            rest: $this->option('rest')
        );
    }

    /**
     * Set the options on the queue listener.
     *
     * @param  \Illuminate\Queue\Listener  $listener
     * @return void
     */
    protected function setOutputHandler(Listener $listener)
    {
        $listener->setOutputHandler(function ($type, $line) {
            $this->output->write($line);
        });
    }
}
