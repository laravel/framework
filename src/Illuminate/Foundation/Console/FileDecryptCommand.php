<?php

namespace Illuminate\Foundation\Console;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Encryption\Encrypter;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Env;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[AsCommand(name: 'file:decrypt')]
class FileDecryptCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'file:decrypt
                    {--key= : The encryption key}
                    {--cipher= : The encryption cipher}
                    {--path= : Path to write the decrypted file}
                    {--filename= : Filename of the decrypted file}
                    {--force : Overwrite the existing file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Decrypt an file';

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
        $filename = $this->option('filename');

        $key = $this->option('key') ?: Env::get('LARAVEL_ENV_ENCRYPTION_KEY');

        if (! $filename && $this->input->isInteractive()) {
            $filename = text('What is the filename to decrypt?');
        }

        if (! $filename) {
            $this->fail('A filename is required.');
        }

        if (! $key && $this->input->isInteractive()) {
            $key = password('What is the decryption key?');
        }

        if (! $key) {
            $this->fail('A decryption key is required.');
        }

        $key = $this->parseKey($key);

        $cipher = $this->option('cipher') ?: 'AES-256-CBC';

        $encryptedFile = Str::finish($this->option('path') ?: $this->laravel->basePath(), DIRECTORY_SEPARATOR).$filename;

        $mainFile = Str::remove('.encrypted', $encryptedFile);

        if (! Str::endsWith($encryptedFile, '.encrypted')) {
            $this->fail('Invalid filename.');
        }

        if (! $this->files->exists($encryptedFile)) {
            $this->fail('Encrypted file not found.');
        }

        if ($this->files->exists($mainFile) && ! $this->option('force')) {
            $this->fail('File already exists.');
        }

        try {
            $encrypter = new Encrypter($key, $cipher);

            $this->files->put(
                $mainFile,
                $encrypter->decrypt($this->files->get($encryptedFile))
            );
        } catch (Exception $e) {
            $this->fail($e->getMessage());
        }

        $this->components->info('File successfully decrypted.');

        $this->components->twoColumnDetail('Decrypted file', $mainFile);

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
