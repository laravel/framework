<?php

namespace Illuminate\Foundation\Console;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Encryption\Encrypter;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Env;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
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
                    {--path= : Path to write the decrypted file}
                    {--filename= : Filename of the decrypted file}
                    {--only-values : Enable if the encrypted file was encrypted with the same flag}';

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

        $encryptedFile = ($this->option('env')
                    ? base_path('.env').'.'.$this->option('env')
                    : $this->laravel->environmentFilePath()).'.encrypted';

        $outputFile = $this->outputFilePath();

        if (Str::endsWith($outputFile, '.encrypted')) {
            $this->components->error('Invalid filename.');

            return Command::FAILURE;
        }

        if (! $this->files->exists($encryptedFile)) {
            $this->components->error('Encrypted environment file not found.');

            return Command::FAILURE;
        }

        if ($this->files->exists($outputFile) && ! $this->option('force')) {
            $this->components->error('Environment file already exists.');

            return Command::FAILURE;
        }

        try {
            $encrypter = new Encrypter($key, $cipher);

            $contents = $this->files->get($encryptedFile);

            if ($this->option('only-values')) {
                $decryptedContents = $this->decryptValues($contents, $encrypter);
            } else {
                $decryptedContents = $encrypter->decrypt($contents);
            }

            $this->files->put(
                $outputFile,
                $decryptedContents
            );
        } catch (Exception $e) {
            $this->components->error($e->getMessage());

            return Command::FAILURE;
        }

        $this->components->info('Environment successfully decrypted.');

        $this->components->twoColumnDetail('Decrypted file', $outputFile);

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

    /**
     * Get the output file path that should be used for the command.
     *
     * @return string
     */
    protected function outputFilePath()
    {
        $path = Str::finish($this->option('path') ?: base_path(), DIRECTORY_SEPARATOR);

        $outputFile = $this->option('filename') ?: ('.env'.($this->option('env') ? '.'.$this->option('env') : ''));
        $outputFile = ltrim($outputFile, DIRECTORY_SEPARATOR);

        return $path.$outputFile;
    }

    protected function decryptValues(string $contents, Encrypter $encrypter): string
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
                        ->pipe(fn (Stringable $value) => $encrypter->decrypt($value->toString()))
                );
        })->toArray());
    }
}
