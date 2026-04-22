<?php

namespace Illuminate\Contracts\Queue;

interface Interruptible
{
    public function interrupted(int $signal): void;
}
