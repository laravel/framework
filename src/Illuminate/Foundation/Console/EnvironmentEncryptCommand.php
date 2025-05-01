<?php

namespace Illuminate\Foundation\Console;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Encryption\Encrypter;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\password;
use function Laravel\Prompts\select;

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
                    {--env= : The environment to be encrypted}
                    {--prune : Delete the original environment file}
                    {--force : Overwrite the existing encrypted environment file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Encrypt an environment file';

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
        $cipher = $this->option('cipher') ?: 'AES-256-CBC';

        $key = $this->option('key');

        if (! $key && $this->input->isInteractive()) {
            $ask = select(
                label: 'What encryption key would you like to use?',
                options: [
                    'generate' => 'Generate a random encryption key',
                    'ask' => 'Provide an encryption key',
                ],
                default: 'generate'
            );

            if ($ask == 'ask') {
                $key = password('What is the encryption key?');
            }
        }

        $keyPassed = $key !== null;

        $environmentFile = $this->option('env')
                            ? Str::finish(dirname($this->laravel->environmentFilePath()), DIRECTORY_SEPARATOR).'.env.'.$this->option('env')
                            : $this->laravel->environmentFilePath();

        $encryptedFile = $environmentFile.'.encrypted';

        if (! $keyPassed) {
            $key = Encrypter::generateKey($cipher);
        }

        if (! $this->files->exists($environmentFile)) {
            $this->fail('Environment file not found.');
        }

        if ($this->files->exists($encryptedFile) && ! $this->option('force')) {
            $this->fail('Encrypted environment file already exists.');
        }

        try {
            $encrypter = new Encrypter($this->parseKey($key), $cipher);

            $this->files->put(
                $encryptedFile,
                $encrypter->encrypt($this->files->get($environmentFile))
            );
        } catch (Exception $e) {
            $this->fail($e->getMessage());
        }

        if ($this->option('prune')) {
            $this->files->delete($environmentFile);
        }

        $this->components->info('Environment successfully encrypted.');

        $this->components->twoColumnDetail('Key', $keyPassed ? $key : 'base64:'.base64_encode($key));
        $this->components->twoColumnDetail('Cipher', $cipher);
        $this->components->twoColumnDetail('Encrypted file', $encryptedFile);

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
