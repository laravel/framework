<?php

namespace Illuminate\Console\Events;

class FileGenerated
{
    /**
     * Create a new event instance.
     *
     * @param  string  $path  The full path of the generated file.
     * @param  string  $type  The type of file (e.g., Controller, Migration, etc.).
     */
    public function __construct(
        public string $path,
        public string $type,
    ) {
    }
}
