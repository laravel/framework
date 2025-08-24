<?php

namespace Illuminate\Console\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FileGenerated
{
    use Dispatchable, SerializesModels;

    public string $path;
    public string $type;

    public function __construct(string $path, string $type)
    {
        $this->path = $path;
        $this->type = $type;
    }
}
