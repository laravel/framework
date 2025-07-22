<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class EnvCopyCommand extends Command
{
    protected $signature = 'env:copy 
        {--force : Overwrite the .env file if it already exists}
        {--env= : Specify the source environment file (default: .env.example)}';

    protected $description = 'Copy the specified environment file to .env';

    protected $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle()
    {
        $source = trim($this->option('env') ?? '.env.example');
        $destination = base_path('.env');

        // Validate source file path
        if (empty($source) || !Str::startsWith($source, '.')) {
            $this->error('Invalid source file path. Please provide a valid file name starting with "." (e.g., .env.example).');
            return self::FAILURE;
        }

        // Check if source file exists
        if (!$this->files->exists(base_path($source))) {
            $this->error("The source file {$source} does not exist in the project root.");
            return self::FAILURE;
        }

        // Check if destination file exists and --force is not used
        if ($this->files->exists($destination) && !$this->option('force')) {
            $this->warn('The .env file already exists. Use --force to overwrite it.');
            return self::FAILURE;
        }

        // Perform the copy operation
        try {
            $this->files->copy(base_path($source), $destination);
            $this->info("Environment file copied successfully from {$source} to .env.");
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to copy environment file: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}