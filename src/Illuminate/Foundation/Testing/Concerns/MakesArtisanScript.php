<?php

namespace Illuminate\Foundation\Testing\Concerns;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait MakesArtisanScript
{
    /**
     * The file system.
     *
     * @var Filesystem|null
     */
    protected $fileSystem;

    /**
     * The contents of the original Artisan file if it exists.
     *
     * @var string|null
     */
    protected $originalArtisanFile;

    /**
     * Create an Artisan script in the Laravel testbench core for external testing.
     *
     * @param  array<string, callable>|callable  $slots
     * @return string
     */
    public function setUpArtisanScript($slots = []): string
    {
        $this->fileSystem = new Filesystem;
        $path = base_path('artisan');

        $uuid = Str::random(32);

        // Save existing artisan script if there is one.
        if ($this->fileSystem->exists($path)) {
            $this->originalArtisanFile = $this->fileSystem->get($path);
        }

        $this->fileSystem->put(
            base_path('artisan'),
            $this->buildArtisanScript($uuid, $slots)
        );

        return $uuid;
    }

    /**
     * Delete an Artisan script and revert it to the cached original.
     *
     * @return void
     */
    public function tearDownArtisanScript(): void
    {
        $this->fileSystem->delete(base_path('artisan'));
        if (! is_null($this->originalArtisanFile)) {
            $this->fileSystem->put(base_path('artisan'), $this->originalArtisanFile);
        }
    }

    /**
     * Execute the Artisan script in a separate process and return the output and exit code.
     *
     * @param  string  $command
     * @return array
     */
    public function artisanScript($command): array
    {
        $output = $exitCode = null;
        exec('php '.base_path('artisan').' '.$command, $output, $exitCode);

        return [$output, $exitCode];
    }

    /**
     * Build a custom Artisan script containing specific scripts.
     *
     * @param  string  $uuid
     * @param  array<string, callable>|callable  $slots
     * @return string
     */
    protected function buildArtisanScript($uuid, $slots = []): string
    {
        // If no array is passed, the default "preHandle" slot is assumed.
        $slots = ! is_array($slots) ? ['preHandle' => $slots] : $slots;

        $thisFile = __FILE__;

        $slots = array_merge([
            'preBootstrap' => '', 'preKernel' => '', 'preHandle' => '', 'preTerminate' => '', 'preExit' => '',
        ], Arr::map($slots, fn ($part) => value($part, $uuid)));

        return <<<PHP
#!/usr/bin/env php
<?php

define('LARAVEL_START', microtime(true));

// This is a custom artisan testing script made specifically for:
// File: {$thisFile}
// Uuid: {$uuid}

require __DIR__.'/../../../autoload.php';

{$slots['preBootstrap']}

\$app = require_once __DIR__.'/bootstrap/app.php';

{$slots['preKernel']}

\$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);

{$slots['preHandle']}

\$status = \$kernel->handle(
    \$input = new Symfony\Component\Console\Input\ArgvInput,
    new Symfony\Component\Console\Output\ConsoleOutput
);

{$slots['preTerminate']}

\$kernel->terminate(\$input, \$status);

{$slots['preExit']}

exit(\$status);

PHP;
    }
}
