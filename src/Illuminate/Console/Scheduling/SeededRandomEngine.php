<?php

declare(strict_types=1);

namespace Illuminate\Console\Scheduling;

use Random\Engine;

class SeededRandomEngine implements Engine
{
    /**
     * @param string $seed
     */
    public function __construct(
        #[\SensitiveParameter]
        private $seed,
    ) {
    }

    /**
     * @return string
     */
    public function generate(): string
    {
        return $this->seed;
    }
}
