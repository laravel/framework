<?php

namespace Illuminate\Foundation\Console;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Encryption\FileEncrypter;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Env;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Finder\Finder;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;

#[AsCommand(name: 'file:decrypt')]
class FileDecryptCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'file:decrypt
                    {path? : The encrypted file or directory path to decrypt}
                    {--key= : The decryption key}
                    {--R|recursive : Recursively decrypt files in directories}
                    {--force : Skip confirmation prompts}
                    {--keep : Keep encrypted files after decryption}
                    {--output= : Custom output path for decrypted file}
                    {--scan : Scan entire project for .enc files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Decrypt a file or directory encrypted with file:encrypt';

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
     * @return int
     */
    public function handle()
    {
        $key = $this->option('key') ?: Env::get('FILE_ENCRYPTION_KEY');

        if (! $key && $this->input->isInteractive()) {
            $key = password('What is the decryption key?');
        }

        if (! $key) {
            $this->fail('A decryption key is required.');
        }

        $key = $this->parseKey($key);

        // Validate key length for AES-256-GCM
        if (mb_strlen($key, '8bit') !== 32) {
            $this->fail('The decryption key must be 32 bytes for AES-256-GCM.');
        }

        $path = $this->argument('path');

        if (! $path && ! $this->option('scan')) {
            $this->fail('Please provide a path or use the --scan option to find encrypted files.');
        }

        // Collect files to decrypt
        $files = $this->option('scan')
            ? $this->scanForEncryptedFiles()
            : $this->collectFiles($path);

        if (empty($files)) {
            $this->fail('No encrypted files found to decrypt.');
        }

        $encrypter = new FileEncrypter($key);

        // Filter to only valid encrypted files
        $filesToDecrypt = [];
        $skippedFiles = [];

        foreach ($files as $file) {
            if (! $encrypter->isEncrypted($file)) {
                $skippedFiles[] = ['path' => $file, 'reason' => 'Not a valid encrypted file'];
                continue;
            }

            $outputPath = $this->getOutputPath($file);

            if ($this->files->exists($outputPath) && ! $this->option('force')) {
                $skippedFiles[] = ['path' => $file, 'reason' => 'Output file already exists'];
                continue;
            }

            $filesToDecrypt[] = ['source' => $file, 'output' => $outputPath];
        }

        if (empty($filesToDecrypt)) {
            if (! empty($skippedFiles)) {
                $this->components->warn('No files could be decrypted.');
                $this->newLine();

                foreach ($skippedFiles as $skipped) {
                    $this->components->twoColumnDetail($skipped['path'], $skipped['reason']);
                }
            }

            return 1;
        }

        // Show confirmation
        if (! $this->option('force')) {
            $this->components->info('The following files will be decrypted:');
            $this->newLine();

            $totalSize = 0;

            foreach ($filesToDecrypt as $file) {
                $size = $this->files->size($file['source']);
                $totalSize += $size;
                $this->components->twoColumnDetail($file['source'], '-> '.$file['output']);
            }

            $this->newLine();
            $this->components->twoColumnDetail('Total encrypted size', $this->formatBytes($totalSize));
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

        // Decrypt files
        $decryptedCount = 0;
        $failedFiles = [];

        foreach ($filesToDecrypt as $file) {
            try {
                $this->decryptFile($encrypter, $file['source'], $file['output']);
                $decryptedCount++;
            } catch (Exception $e) {
                $failedFiles[] = ['path' => $file['source'], 'reason' => $e->getMessage()];
            }
        }

        // Output results
        $this->newLine();

        if ($decryptedCount > 0) {
            $this->components->info(
                $decryptedCount === 1
                    ? 'File successfully decrypted.'
                    : "{$decryptedCount} files successfully decrypted."
            );
        }

        if (! empty($failedFiles)) {
            $this->newLine();
            $this->components->error('The following files failed to decrypt:');
            $this->newLine();

            foreach ($failedFiles as $failed) {
                $this->components->twoColumnDetail($failed['path'], $failed['reason']);
            }
        }

        $this->newLine();

        return empty($failedFiles) ? 0 : 1;
    }

    /**
     * Collect encrypted files from the given path.
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

        // Find .enc files
        $finder = Finder::create()
            ->files()
            ->in($absolutePath)
            ->name('*.enc')
            ->ignoreDotFiles(false);

        if (! $this->option('recursive')) {
            $finder->depth(0);
        }

        return array_map(
            fn ($file) => $file->getPathname(),
            iterator_to_array($finder)
        );
    }

    /**
     * Scan the entire project for encrypted files.
     *
     * @return array
     */
    protected function scanForEncryptedFiles(): array
    {
        $basePath = $this->laravel->basePath();

        if (! $this->option('force')) {
            $this->components->warn('Scanning the entire project for .enc files may take a while.');
            $this->newLine();

            if (! confirm('Do you want to proceed with the scan?', default: true)) {
                $this->fail('Scan cancelled.');
            }

            $this->newLine();
            $this->output->write('  Scanning for encrypted files... ');
        }

        $finder = Finder::create()
            ->files()
            ->in($basePath)
            ->name('*.enc')
            ->ignoreDotFiles(false)
            ->exclude([
                'vendor',
                'node_modules',
                '.git',
                'storage/framework',
                'bootstrap/cache',
            ]);

        $files = array_map(
            fn ($file) => $file->getPathname(),
            iterator_to_array($finder)
        );

        if (! $this->option('force')) {
            $this->output->writeln('<info>done</info>');
            $this->components->info(count($files).' encrypted file(s) found.');
            $this->newLine();
        }

        return $files;
    }

    /**
     * Get the output path for a decrypted file.
     *
     * @param  string  $encryptedPath
     * @return string
     */
    protected function getOutputPath(string $encryptedPath): string
    {
        if ($this->option('output')) {
            // If a single file is being decrypted and output is specified
            return $this->getAbsolutePath($this->option('output'));
        }

        // Remove .enc extension
        return preg_replace('/\.enc$/', '', $encryptedPath);
    }

    /**
     * Decrypt a single file.
     *
     * @param  \Illuminate\Encryption\FileEncrypter  $encrypter
     * @param  string  $sourcePath
     * @param  string  $outputPath
     * @return void
     */
    protected function decryptFile(FileEncrypter $encrypter, string $sourcePath, string $outputPath): void
    {
        $tempPath = $outputPath.'.tmp.'.Str::random(8);

        try {
            $this->output->write("  Decrypting: {$sourcePath}... ");

            $encrypter->decryptFile($sourcePath, $tempPath, function ($current, $total) {
                // Progress is handled by the encrypter
            });

            $this->output->writeln('<info>done</info>');

            // Atomic rename
            $this->files->move($tempPath, $outputPath);

            // Delete encrypted file unless --keep
            if (! $this->option('keep')) {
                $this->files->delete($sourcePath);
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
