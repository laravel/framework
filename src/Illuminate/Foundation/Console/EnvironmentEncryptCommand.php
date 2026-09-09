<?php

namespace Illuminate\Foundation\Console;

use Dotenv\Parser\Lines;
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
                    {--readable : Encrypt each variable individually with readable, plain-text variable names}
                    {--incremental : Update a readable encrypted file, preserving unchanged values}
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
        if ($this->option('incremental') && ! $this->option('readable')) {
            $this->fail('The --incremental option requires --readable.');
        }

        $cipher = $this->option('cipher') ?: 'AES-256-CBC';

        $environmentFile = $this->option('env')
            ? Str::finish($this->laravel->environmentPath(), DIRECTORY_SEPARATOR).'.env.'.$this->option('env')
            : $this->laravel->environmentFilePath();

        $encryptedFile = $environmentFile.'.encrypted';

        if (! $this->files->exists($environmentFile)) {
            $this->fail('Environment file not found.');
        }

        $encryptedFileExists = $this->option('incremental') ? $this->files->exists($encryptedFile) : null;

        $key = $this->option('key');

        if (! $key && $this->input->isInteractive() && $this->option('incremental') && $encryptedFileExists) {
            $key = password('What is the encryption key?');
        } elseif (! $key && $this->input->isInteractive()) {
            $ask = select(
                label: 'What encryption key would you like to use?',
                options: [
                    'generate' => 'Generate a random encryption key',
                    'ask' => 'Provide an encryption key',
                ],
                default: 'generate'
            );

            if ($ask === 'ask') {
                $key = password('What is the encryption key?');
            }
        }

        if ($encryptedFileExists && $this->option('incremental') && ($key === null || $key === '')) {
            $this->fail('The existing encryption key is required for incremental encryption.');
        }

        $keyPassed = $key !== null;

        if (! $keyPassed) {
            $key = Encrypter::generateKey($cipher);
        }

        $encryptedFileExists ??= $this->files->exists($encryptedFile);

        if ($encryptedFileExists && ! $this->option('force') && ! $this->option('incremental')) {
            $this->fail('Encrypted environment file already exists.');
        }

        try {
            $encrypter = new Encrypter($this->parseKey($key), $cipher);

            $contents = $this->files->get($environmentFile);
            $previous = $this->option('incremental') && $encryptedFileExists
                ? $this->files->get($encryptedFile)
                : null;

            $encrypted = $this->option('readable')
                ? $this->encryptReadableFormat($contents, $encrypter, $previous)
                : $encrypter->encrypt($contents);

            if ($encrypted !== $previous) {
                $written = $this->files->put($encryptedFile, $encrypted);

                if ($this->option('incremental') && $written === false) {
                    $this->fail('Unable to write the encrypted environment file.');
                }
            }
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
     * Encrypt the environment file in readable format.
     *
     * @param  string  $contents
     * @param  \Illuminate\Encryption\Encrypter  $encrypter
     * @param  string|null  $previous
     * @return string
     */
    protected function encryptReadableFormat(string $contents, Encrypter $encrypter, ?string $previous = null): string
    {
        $result = '';
        $existing = [];
        $previousOutput = '';

        foreach ($previous === null ? [] : $this->readEncryptedEntries($previous, $encrypter) as $entry) {
            $existing[$entry['name']][] = $entry;
            $previousOutput .= $entry['name'].'='.$entry['encrypted']."\n";
        }

        foreach (Lines::process(preg_split('/\r\n|\r|\n/', $contents)) as $entry) {
            $pos = strpos($entry, '=');

            if ($pos === false) {
                continue;
            }

            $name = substr($entry, 0, $pos);
            $value = substr($entry, $pos + 1);

            $existingEntry = empty($existing[$name]) ? null : array_shift($existing[$name]);

            $result .= $name.'='.($existingEntry !== null && $existingEntry['value'] === $value
                ? $existingEntry['encrypted']
                : $encrypter->encryptString($value))."\n";
        }

        return $previous !== null && $result === $previousOutput ? $previous : $result;
    }

    /**
     * Authenticate the existing readable entries, preserving order and duplicate names.
     *
     * @param  string  $contents
     * @param  \Illuminate\Encryption\Encrypter  $encrypter
     * @return array
     */
    protected function readEncryptedEntries(string $contents, Encrypter $encrypter): array
    {
        if (Encrypter::appearsEncrypted($contents)) {
            $this->fail('Incremental encryption requires an existing file in readable format.');
        }

        $entries = [];

        foreach (preg_split('/\r\n|\r|\n/', $contents) as $index => $line) {
            if (trim($line) === '' || str_starts_with(ltrim($line), '#')) {
                continue;
            }

            $pos = strpos($line, '=');

            if ($pos === false || $pos === 0) {
                $this->fail('Invalid encrypted environment entry on line '.($index + 1).'.');
            }

            $encrypted = substr($line, $pos + 1);

            try {
                $value = $encrypter->decryptString($encrypted);
            } catch (Exception $e) {
                $this->fail('Unable to decrypt the encrypted environment entry on line '.($index + 1).'.');
            }

            $name = substr($line, 0, $pos);

            $entries[] = compact('name', 'value', 'encrypted');
        }

        return $entries;
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
