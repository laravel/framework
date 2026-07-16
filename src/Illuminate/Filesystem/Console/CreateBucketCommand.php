<?php

namespace Illuminate\Filesystem\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'storage:create-bucket')]
class CreateBucketCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'storage:create-bucket
                {name? : The name of the bucket to create}
                {--disk=s3 : The S3 filesystem disk to use}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a bucket for an S3 filesystem disk';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $diskName = $this->option('disk');
        $config = $this->laravel['config']->get("filesystems.disks.{$diskName}", []);

        if (($config['driver'] ?? null) !== 's3') {
            $this->components->error("The [{$diskName}] disk does not use the S3 driver.");

            return static::FAILURE;
        }

        $bucket = $this->argument('name') ?: ($config['bucket'] ?? null);

        if (! $bucket) {
            $this->components->error("The [{$diskName}] disk does not have a configured bucket.");

            return static::FAILURE;
        }

        $disk = $this->laravel['filesystem']->disk($diskName);

        $disk->getClient()->createBucket(['Bucket' => $bucket]);

        $this->components->info("Bucket [{$bucket}] created successfully.");

        return static::SUCCESS;
    }
}
