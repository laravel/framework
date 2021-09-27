<?php

declare(strict_types=1);

namespace Illuminate\Console\Concerns;

use Illuminate\Support\Str;

trait CreatesSupportingTests
{

    /**
     * Create a supporting test for the created file.
     *
     * @param string $path
     * @return void
     */
    protected function createTest($path)
    {
        $testName = Str::of($path)->after($this->laravel['path'])->beforeLast('.php')->append('Test');
        $this->call('make:test', ['name' => $testName]);
    }

}
