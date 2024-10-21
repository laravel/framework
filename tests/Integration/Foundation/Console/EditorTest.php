<?php

namespace Illuminate\Tests\Integration\Foundation\Console;

use Illuminate\Foundation\Console\Editor;
use Illuminate\Support\Facades\Process;
use Orchestra\Testbench\TestCase;

class EditorTest extends TestCase
{
    public function testEditorRunsNothingWhenBinaryIsNotGiven()
    {
        $editor = new Editor(null);

        Process::fake();

        $editor->open('path/to/file');

        Process::assertNothingRan();
    }

    public function testEditorRunsBinary()
    {
        $editor = new Editor('my-ide');

        Process::fake();

        $editor->open('path/to/file');

        Process::assertRan(function ($process) {
            return $process->command == ['my-ide', 'path/to/file'];
        });
    }
}
