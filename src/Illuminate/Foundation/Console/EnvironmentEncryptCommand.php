<?php

namespace Illuminate\Foundation\Console;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Encryption\Encrypter;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
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
                    {--env= : The environment to be encrypted}
                    {--force : Overwrite the existing encrypted environment file}
                    {--only-values : Encrypt only the values to keep the file readable}';

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

        $keyPassed = $key !== null;

        $environmentFile = $this->option('env')
                            ? base_path('.env').'.'.$this->option('env')
                            : $this->laravel->environmentFilePath();

        $encryptedFile = $environmentFile.'.encrypted';

        if (! $keyPassed) {
            $key = Encrypter::generateKey($cipher);
        }

        if (! $this->files->exists($environmentFile)) {
            $this->components->error('Environment file not found.');

            return Command::FAILURE;
        }

        if ($this->files->exists($encryptedFile) && ! $this->option('force')) {
            $this->components->error('Encrypted environment file already exists.');

            return Command::FAILURE;
        }

        try {
            $encrypter = new Encrypter($this->parseKey($key), $cipher);

            $contents = $this->files->get($environmentFile);

            if ($this->option('only-values')) {
                $encryptedContents = $this->encryptValues($contents, $encrypter);
            } else {
                $encryptedContents = $encrypter->encrypt($contents);
            }

            $this->files->put(
                $encryptedFile,
                $encryptedContents
            );

        } catch (Exception $e) {
            $this->components->error($e->getMessage());

            return Command::FAILURE;
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

    protected function encryptValues(string $contents, Encrypter $encrypter): string
    {
        return implode(PHP_EOL, collect(explode(PHP_EOL, $contents))->map(function (string $line) use ($encrypter) {
            $line = Str::of($line);

            if (! $line->contains('=')) {
                return $line;
            }

            return $line->before('=')
                ->append('=')
                ->append(
                    $line->after('=')
                        ->pipe(fn (Stringable $value) => $encrypter->encrypt($value->toString()))
                );
        })->toArray());
    }
}
