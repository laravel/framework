<?php

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

return fn () => throw new ProcessFailedException(new class([]) extends Process
{
    public function isSuccessful(): bool
    {
        return false;
    }

    public function getExitCode(): ?int
    {
        return 130;
    }

    public function isOutputDisabled(): bool
    {
        return true;
    }
});
