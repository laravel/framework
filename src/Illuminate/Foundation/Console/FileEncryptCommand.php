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
use function Laravel\Prompts\text;

#[AsCommand(name: 'file:encrypt')]
class FileEncryptCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'file:encrypt
                    {--key= : The encryption key}
                    {--cipher= : The encryption cipher}
                    {--path= : Path to write the encrypted file}
                    {--filename= : Filename of the encrypted file}
                    {--prune : Delete the original file}
                    {--force : Overwrite the existing encrypted file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Encrypt an file';

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
        $key = $this->option('key');

        $filename = $this->option('filename');

        $cipher = $this->option('cipher') ?: 'AES-256-CBC';

        if (! $filename && $this->input->isInteractive()) {
            $filename = text('What is the filename to encrypt?');
        }

        if (! $filename) {
            $this->fail('A filename is required.');
        }

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

        $mainFile = Str::finish($this->option('path') ?: $this->laravel->basePath(), DIRECTORY_SEPARATOR).$filename;

        $encryptedFile = $mainFile.'.encrypted';

        if (! $keyPassed) {
            $key = Encrypter::generateKey($cipher);
        }

        if (! $this->files->exists($mainFile)) {
            $this->fail('File not found.');
        }

        if ($this->files->exists($encryptedFile) && ! $this->option('force')) {
            $this->fail('Encrypted file already exists.');
        }

        try {
            $encrypter = new Encrypter($this->parseKey($key), $cipher);

            $this->files->put(
                $encryptedFile,
                $encrypter->encrypt($this->files->get($mainFile))
            );
        } catch (Exception $e) {
            $this->fail($e->getMessage());
        }

        if ($this->option('prune')) {
            $this->files->delete($mainFile);
        }

        $this->components->info('File successfully encrypted.');

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
