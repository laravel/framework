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
     * @param  array<string, callable>|callable  $parts
     * @return string
     */
    public function setUpArtisanScript($parts = []): string
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
            $this->buildArtisanScript($uuid, $parts)
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
     * Build a custom Artisan script containing specific scripts.
     *
     * @param  string  $uuid
     * @param  array<string, callable>|callable  $parts
     * @return string
     */
    protected function buildArtisanScript($uuid, $parts = []): string
    {
        // If no array is passed, the default "preHandle" slot is assumed.
        $parts = !is_array($parts) ? ['preHandle' => $parts] : $parts;

        $thisFile = __FILE__;

        $parts = array_merge([
            'preKernel' => '',
            'preHandle' => '',
            'postHandle' => '',
        ], Arr::map($parts, fn ($part) => value($part, $uuid)));

        return <<<PHP
#!/usr/bin/env php
<?php

define('LARAVEL_START', microtime(true));

// This is a custom artisan testing script made specifically for:
// File: {$thisFile}
// Uuid: {$uuid}

require __DIR__.'/../../../autoload.php';

\$app = require_once __DIR__.'/bootstrap/app.php';

{$parts['preKernel']}

\$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);

{$parts['preHandle']}

\$status = \$kernel->handle(
    \$input = new Symfony\Component\Console\Input\ArgvInput,
    new Symfony\Component\Console\Output\ConsoleOutput
);

{$parts['postHandle']}

\$kernel->terminate(\$input, \$status);

exit(\$status);

PHP;
    }
}
