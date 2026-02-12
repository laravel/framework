<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'logs:rotate')]
class LogRotateCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'logs:rotate
                {--keep=50 : The number of rotated files to keep per log}
                {--path= : The path to the log directory}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rotate the log files in the configured log directory';

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Create a new log rotate command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $logsPath = $this->resolveLogsPath();

        if (! $this->files->isDirectory($logsPath)) {
            $this->components->error("The log directory [{$logsPath}] does not exist.");

            return self::FAILURE;
        }

        $keep = (int) $this->option('keep');

        $maxSeq = $this->findMaxSequence($logsPath);

        $this->bumpSequences($logsPath, $maxSeq);

        $rotated = 0;

        foreach ($this->files->glob("{$logsPath}/*.log") as $logFile) {
            if ($this->files->size($logFile) === 0) {
                continue;
            }

            $baseName = basename($logFile);
            $datePart = date('Ymd-Hi', $this->files->lastModified($logFile));
            $rotatedName = "{$baseName}-{$datePart}.1";

            $this->files->move($logFile, "{$logsPath}/{$rotatedName}");

            $this->components->info("Rotated [{$baseName}] to [{$rotatedName}].");
            $rotated++;
        }

        $this->pruneOldRotations($logsPath, $keep);

        $this->components->info("Rotated {$rotated} log file(s).");

        return self::SUCCESS;
    }

    /**
     * Resolve the path to the log directory.
     *
     * @return string
     */
    protected function resolveLogsPath(): string
    {
        if ($path = $this->option('path')) {
            return $path;
        }

        $channels = $this->laravel['config']->get('logging.channels', []);

        foreach ($channels as $channel) {
            if (isset($channel['driver'], $channel['path']) &&
                in_array($channel['driver'], ['single', 'daily'])) {
                return dirname($channel['path']);
            }
        }

        return $this->laravel->storagePath('logs');
    }

    /**
     * Find the highest sequence number across all rotated log files.
     *
     * @param  string  $logsPath
     * @return int
     */
    protected function findMaxSequence(string $logsPath): int
    {
        $maxSeq = 0;

        foreach ($this->files->glob("{$logsPath}/*.log-*") as $file) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);

            if (is_numeric($ext)) {
                $maxSeq = max($maxSeq, (int) $ext);
            }
        }

        return $maxSeq;
    }

    /**
     * Bump all existing rotated files up by one sequence number.
     *
     * Works high-to-low to avoid collisions.
     *
     * @param  string  $logsPath
     * @param  int  $maxSeq
     * @return void
     */
    protected function bumpSequences(string $logsPath, int $maxSeq): void
    {
        for ($seq = $maxSeq; $seq >= 1; $seq--) {
            foreach ($this->files->glob("{$logsPath}/*.log-*.{$seq}") as $file) {
                $newFile = preg_replace('/\.\d+$/', '.'.($seq + 1), $file);

                $this->files->move($file, $newFile);
            }
        }
    }

    /**
     * Remove rotated logs with a sequence number beyond the keep limit.
     *
     * @param  string  $logsPath
     * @param  int  $keep
     * @return void
     */
    protected function pruneOldRotations(string $logsPath, int $keep): void
    {
        foreach ($this->files->glob("{$logsPath}/*.log-*") as $file) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);

            if (is_numeric($ext) && (int) $ext > $keep) {
                $this->files->delete($file);

                $this->components->info('Pruned ['.basename($file).'].');
            }
        }
    }
}
