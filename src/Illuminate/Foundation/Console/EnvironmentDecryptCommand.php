<?php

namespace Illuminate\Foundation\Console;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Encryption\Encrypter;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Env;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'env:decrypt')]
class EnvironmentDecryptCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'env:decrypt
                    {--key= : The encryption key}
                    {--cipher= : The encryption cipher}
                    {--env= : The environment to be decrypted}
                    {--force : Overwrite the existing environment file}
                    {--filename= : Where to write the decrypted file contents}';

    /**
     * The name of the console command.
     *
     * This name is used to identify the command during lazy loading.
     *
     * @var string|null
     *
     * @deprecated
     */
    protected static $defaultName = 'env:decrypt';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Decrypt an environment file';

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Create a new command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return void
     */
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
        $key = $this->option('key') ?: Env::get('LARAVEL_ENV_ENCRYPTION_KEY');

        if (! $key) {
            $this->components->error('A decryption key is required.');

            return Command::FAILURE;
        }

        $cipher = $this->option('cipher') ?: 'AES-256-CBC';

        $key = $this->parseKey($key);

        $environmentFile = $this->option('env')
                    ? base_path('.env').'.'.$this->option('env')
                    : $this->laravel->environmentFilePath();

        $encryptedFile = $environmentFile.'.encrypted';

        $filename = $this->option('filename')
                    ? base_path($this->option('filename'))
                    : $environmentFile;

        if (Str::endsWith($filename, '.encrypted')) {
            $this->components->error('Invalid filename.');

            return Command::FAILURE;
        }

        if (! $this->files->exists($encryptedFile)) {
            $this->components->error('Encrypted environment file not found.');

            return Command::FAILURE;
        }

        if ($this->files->exists($environmentFile) && ! $this->option('force')) {
            $this->components->error('Environment file already exists.');

            return Command::FAILURE;
        }

        try {
            $encrypter = new Encrypter($key, $cipher);

            $this->files->put(
                $filename,
                $encrypter->decrypt($this->files->get($encryptedFile))
            );
        } catch (Exception $e) {
            $this->components->error($e->getMessage());

            return Command::FAILURE;
        }

        $this->components->info('Environment successfully decrypted.');

        $this->components->twoColumnDetail('Decrypted file', $filename);

        $this->newLine();
    }

    /**
     * Parse the encryption key.
     *
     * @param  string  $key
     * @return string
     */
    protected function parseKey(string $key)
    {
        if (Str::startsWith($key, $prefix = 'base64:')) {
            $key = base64_decode(Str::after($key, $prefix));
        }

        return $key;
    }
}
