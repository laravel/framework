<?php

namespace Illuminate\Foundation\Console;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Encryption\Encrypter;
use Illuminate\Encryption\FileEncrypter;
use Illuminate\Encryption\FileEncryptionPathValidator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Env;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Finder\Finder;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\select;

#[AsCommand(name: 'file:encrypt')]
class FileEncryptCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'file:encrypt
                    {path : The file or directory path to encrypt}
                    {--key= : The encryption key}
                    {--R|recursive : Recursively encrypt files in directories}
                    {--force : Skip confirmation prompts}
                    {--chunk-size=65536 : Chunk size in bytes for streaming encryption}
                    {--prune : Delete original files after encryption}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Encrypt a file or directory using streaming encryption';

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The path validator instance.
     *
     * @var \Illuminate\Encryption\FileEncryptionPathValidator
     */
    protected $validator;

    /**
     * Create a new command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  \Illuminate\Encryption\FileEncryptionPathValidator  $validator
     */
    public function __construct(Filesystem $files, FileEncryptionPathValidator $validator)
    {
        parent::__construct();

        $this->files = $files;
        $this->validator = $validator;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $key = $this->option('key') ?: Env::get('FILE_ENCRYPTION_KEY');

        if (! $key && $this->input->isInteractive()) {
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

        $keyPassed = $key !== null;

        if (! $keyPassed) {
            $key = Encrypter::generateKey('AES-256-GCM');
        }

        $key = $this->parseKey($key);

        // Validate key length for AES-256-GCM
        if (mb_strlen($key, '8bit') !== 32) {
            $this->fail('The encryption key must be 32 bytes for AES-256-GCM.');
        }

        $path = $this->argument('path');
        $chunkSize = (int) $this->option('chunk-size');

        if ($chunkSize < 1024) {
            $this->fail('Chunk size must be at least 1024 bytes.');
        }

        // Set up validator with base path
        $this->validator->setBasePath($this->laravel->basePath());

        // Collect files to encrypt
        $files = $this->collectFiles($path);

        if (empty($files)) {
            $this->fail('No files found to encrypt.');
        }

        // Filter out already encrypted files and validate paths
        $filesToEncrypt = [];
        $skippedFiles = [];

        $encrypter = new FileEncrypter($key, $chunkSize);

        foreach ($files as $file) {
            if ($encrypter->isEncrypted($file)) {
                $skippedFiles[] = ['path' => $file, 'reason' => 'Already encrypted'];
                continue;
            }

            if ($this->files->exists($file.'.enc') && ! $this->option('force')) {
                $skippedFiles[] = ['path' => $file, 'reason' => 'Encrypted file already exists'];
                continue;
            }

            try {
                $this->validator->validateForEncryption($file);
                $filesToEncrypt[] = $file;
            } catch (Exception $e) {
                $skippedFiles[] = ['path' => $file, 'reason' => $e->getMessage()];
            }
        }

        if (empty($filesToEncrypt)) {
            if (! empty($skippedFiles)) {
                $this->components->warn('No files could be encrypted.');
                $this->newLine();

                foreach ($skippedFiles as $skipped) {
                    $this->components->twoColumnDetail($skipped['path'], $skipped['reason']);
                }
            }

            return 1;
        }

        // Show confirmation
        if (! $this->option('force')) {
            $this->components->info('The following files will be encrypted:');
            $this->newLine();

            $totalSize = 0;

            foreach ($filesToEncrypt as $file) {
                $size = $this->files->size($file);
                $totalSize += $size;
                $this->components->twoColumnDetail($file, $this->formatBytes($size));
            }

            $this->newLine();
            $this->components->twoColumnDetail('Total', $this->formatBytes($totalSize));
            $this->newLine();

            if (! empty($skippedFiles)) {
                $this->components->warn('The following files will be skipped:');
                $this->newLine();

                foreach ($skippedFiles as $skipped) {
                    $this->components->twoColumnDetail($skipped['path'], $skipped['reason']);
                }

                $this->newLine();
            }

            if (! confirm('Do you want to proceed?', default: true)) {
                return 1;
            }
        }

        // Encrypt files
        $encryptedCount = 0;
        $failedFiles = [];

        foreach ($filesToEncrypt as $file) {
            try {
                $this->encryptFile($encrypter, $file);
                $encryptedCount++;
            } catch (Exception $e) {
                $failedFiles[] = ['path' => $file, 'reason' => $e->getMessage()];
            }
        }

        // Output results
        $this->newLine();

        if ($encryptedCount > 0) {
            $this->components->info(
                $encryptedCount === 1
                    ? 'File successfully encrypted.'
                    : "{$encryptedCount} files successfully encrypted."
            );

            $this->newLine();
            $generatedKey = 'base64:'.base64_encode($key);
            $this->components->twoColumnDetail('Key', $keyPassed ? '[provided]' : $generatedKey);
            $this->components->twoColumnDetail('Chunk size', $this->formatBytes($chunkSize));

            if (! $keyPassed) {
                $this->newLine();
                $this->components->warn('Please set this key as FILE_ENCRYPTION_KEY in your environment.');
            }

            if (! $this->option('prune')) {
                $this->newLine();
                $this->components->warn('Ensure unencrypted files are added to .gitignore to avoid committing sensitive data.');
            }
        }

        if (! empty($failedFiles)) {
            $this->newLine();
            $this->components->error('The following files failed to encrypt:');
            $this->newLine();

            foreach ($failedFiles as $failed) {
                $this->components->twoColumnDetail($failed['path'], $failed['reason']);
            }
        }

        $this->newLine();

        return empty($failedFiles) ? 0 : 1;
    }

    /**
     * Collect files to encrypt from the given path.
     *
     * @param  string  $path
     * @return array
     */
    protected function collectFiles(string $path): array
    {
        $absolutePath = $this->getAbsolutePath($path);

        if (! $this->files->exists($absolutePath)) {
            $this->fail("Path does not exist: {$path}");
        }

        if ($this->files->isFile($absolutePath)) {
            return [$absolutePath];
        }

        if (! $this->files->isDirectory($absolutePath)) {
            $this->fail("Path is not a file or directory: {$path}");
        }

        if (! $this->option('recursive')) {
            // Only get files in the immediate directory
            return array_map(
                fn ($file) => $file->getPathname(),
                iterator_to_array(
                    Finder::create()
                        ->files()
                        ->in($absolutePath)
                        ->depth(0)
                        ->ignoreDotFiles(false)
                )
            );
        }

        // Recursively get all files
        return array_map(
            fn ($file) => $file->getPathname(),
            iterator_to_array(
                Finder::create()
                    ->files()
                    ->in($absolutePath)
                    ->ignoreDotFiles(false)
            )
        );
    }

    /**
     * Encrypt a single file.
     *
     * @param  \Illuminate\Encryption\FileEncrypter  $encrypter
     * @param  string  $file
     * @return void
     */
    protected function encryptFile(FileEncrypter $encrypter, string $file): void
    {
        $outputPath = $file.'.enc';
        $tempPath = $outputPath.'.tmp.'.Str::random(8);

        try {
            $fileSize = $this->files->size($file);
            $totalChunks = (int) ceil($fileSize / $encrypter->getChunkSize());

            if ($totalChunks > 0) {
                $this->output->write("  Encrypting: {$file}... ");

                $encrypter->encryptFile($file, $tempPath, function ($current, $total) {
                    // Progress is handled by the encrypter
                });

                $this->output->writeln('<info>done</info>');
            } else {
                $this->output->writeln("  Encrypting: {$file}... <info>done</info>");
                $encrypter->encryptFile($file, $tempPath);
            }

            // Atomic rename
            $this->files->move($tempPath, $outputPath);

            // Delete original if --prune
            if ($this->option('prune')) {
                $this->files->delete($file);
            }
        } catch (Exception $e) {
            // Clean up temp file on failure
            if ($this->files->exists($tempPath)) {
                $this->files->delete($tempPath);
            }

            throw $e;
        }
    }

    /**
     * Parse the encryption key.
     *
     * @param  string  $key
     * @return string
     */
    protected function parseKey(string $key): string
    {
        if (Str::startsWith($key, $prefix = 'base64:')) {
            $key = base64_decode(Str::after($key, $prefix));
        }

        return $key;
    }

    /**
     * Get the absolute path for a given path.
     *
     * @param  string  $path
     * @return string
     */
    protected function getAbsolutePath(string $path): string
    {
        if (Str::startsWith($path, DIRECTORY_SEPARATOR)) {
            return $path;
        }

        return $this->laravel->basePath($path);
    }

    /**
     * Format bytes to a human-readable string.
     *
     * @param  int  $bytes
     * @return string
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $index = 0;

        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            $index++;
        }

        return round($bytes, 2).' '.$units[$index];
    }
}
