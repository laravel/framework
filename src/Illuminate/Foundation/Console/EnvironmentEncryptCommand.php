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
                    {--readable : Encrypt in readable format with visible keys}
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

            $contents = $this->files->get($environmentFile);

            $encrypted = $this->option('readable')
                ? $this->encryptReadableFormat($contents, $encrypter)
                : $encrypter->encrypt($contents);

            $this->files->put($encryptedFile, $encrypted);
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

    /**
     * Encrypt the environment file in readable format.
     *
     * @param  string  $contents
     * @param  \Illuminate\Encryption\Encrypter  $encrypter
     * @return string
     */
    protected function encryptReadableFormat(string $contents, Encrypter $encrypter): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $contents);
        $result = [];
        $multilineBuffer = null;
        $multilineKey = null;

        foreach ($lines as $line) {
            // Handle multiline continuation
            if ($multilineBuffer !== null) {
                $multilineBuffer .= "\n".$line;

                if ($this->isMultilineEnd($line)) {
                    $result[] = $multilineKey.'='.$encrypter->encryptString($multilineBuffer);
                    $multilineBuffer = null;
                    $multilineKey = null;
                }

                continue;
            }

            // Preserve blank lines
            if (trim($line) === '') {
                $result[] = '';
                continue;
            }

            // Handle comments - encrypt with #: prefix
            if ($this->isComment($line)) {
                $result[] = '#:'.$encrypter->encryptString($line);
                continue;
            }

            // Parse variable line
            $parsed = $this->parseEnvLine($line);

            if ($parsed === null) {
                // Invalid line - encrypt as comment
                $result[] = '#:'.$encrypter->encryptString($line);
                continue;
            }

            [$key, $value, $isMultilineStart] = $parsed;

            if ($isMultilineStart) {
                $multilineBuffer = $value;
                $multilineKey = $key;
                continue;
            }

            $result[] = $key.'='.$encrypter->encryptString($value);
        }

        // Handle unterminated multiline (edge case)
        if ($multilineBuffer !== null) {
            $result[] = $multilineKey.'='.$encrypter->encryptString($multilineBuffer);
        }

        return implode("\n", $result);
    }

    /**
     * Determine if a line is a comment.
     *
     * @param  string  $line
     * @return bool
     */
    protected function isComment(string $line): bool
    {
        $trimmed = ltrim($line);

        return str_starts_with($trimmed, '#');
    }

    /**
     * Parse an environment variable line.
     *
     * Returns [key, value, isMultilineStart] or null if invalid.
     *
     * @param  string  $line
     * @return array|null
     */
    protected function parseEnvLine(string $line): ?array
    {
        // Handle export prefix
        $line = preg_replace('/^export\s+/', '', ltrim($line));

        // Find the = separator
        $pos = strpos($line, '=');

        if ($pos === false) {
            return null;
        }

        $key = substr($line, 0, $pos);
        $value = substr($line, $pos + 1);

        // Validate key (alphanumeric, underscore, starts with letter or underscore)
        if (! preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $key)) {
            return null;
        }

        // Check for multiline start (value starts with " but doesn't end with ")
        $isMultilineStart = $this->isMultilineStart($value);

        return [$key, $value, $isMultilineStart];
    }

    /**
     * Determine if a value starts a multiline string.
     *
     * @param  string  $value
     * @return bool
     */
    protected function isMultilineStart(string $value): bool
    {
        $trimmed = ltrim($value);

        if (! str_starts_with($trimmed, '"')) {
            return false;
        }

        // Count unescaped quotes
        $unescaped = str_replace('\\\\', '', $trimmed);
        $unescaped = str_replace('\\"', '', $unescaped);

        return substr_count($unescaped, '"') === 1;
    }

    /**
     * Determine if a line ends a multiline string.
     *
     * @param  string  $line
     * @return bool
     */
    protected function isMultilineEnd(string $line): bool
    {
        $unescaped = str_replace('\\\\', '', $line);
        $unescaped = str_replace('\\"', '', $unescaped);

        // Line ends with unescaped quote
        return str_ends_with(rtrim($unescaped), '"');
    }
}
