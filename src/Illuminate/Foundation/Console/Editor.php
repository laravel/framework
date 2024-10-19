<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Container\Attributes\Config;
use Illuminate\Support\Facades\Process;

class Editor
{
    /**
     * The editor command.
     *
     * @var string|null
     */
    public ?string $editor;

    /**
     * Create a new Editor instance.
     *
     * @param  string|null  $editor
     * @return void
     */
    public function __construct(
        #[Config('app.editor')] ?string $editor
    ) {
        $this->editor = $editor;
    }

    /**
     * Open the given path in the editor.
     *
     * @param  string  $path
     * @return void
     */
    public function open(string $path)
    {
        if (! $this->editor) {
            return;
        }

        Process::run([$this->editor, $path]);
    }
}
