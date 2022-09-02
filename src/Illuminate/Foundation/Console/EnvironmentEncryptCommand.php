<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Encryption\Encrypter;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'env:encrypt')]
class EnvironmentEncryptCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'env:encrypt
                    {--key= : The encryption key}
                    {--cipher= : The encryption cipher}
                    {--file= : The environment file to be decrypted}
                    {--force : Overwrite any existing files}';

    /**
     * The name of the console command.
     *
     * This name is used to identify the command during lazy loading.
     *
     * @var string|null
     *
     * @deprecated
     */
    protected static $defaultName = 'env:encrypt';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Encrypt the given environment file';

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $key = $this->option('key') ?: Str::random(16);
        $cipher = $this->option('cipher') ?: 'aes-128-cbc';
        $environment = $this->option('file') ?: $this->laravel->environmentFilePath();
        $encrypted = $environment.'.encrypted';

        if (! $this->files->exists($environment)) {
            return $this->components->error("The environment file {$environment} does not exist.");
        }

        if ($this->files->exists($encrypted) && ! $this->option('force')) {
            return $this->components->error("The encrypted environment file {$encrypted} already exists.");
        }

        $encrypter = new Encrypter($key, $cipher);

        $this->files->put($encrypted, $encrypter->encrypt($this->files->get($environment)));

        $this->components->info("The environment file {$environment} has been encrypted using they key {$key}.");
    }
}
