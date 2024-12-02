<?php

declare(strict_types=1);

namespace Illuminate\Tests\Auth\Fixtures;

class ObjectAbility
{
    public function __construct(
        public bool $granted = true,
    ) {
    }

    public function granted($user)
    {
        return $this->granted;
    }
}
