<?php

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

return fn () => throw new ProcessFailedException(new class(['expected-command']) extends Process
{
    public function isSuccessful(): bool
    {
        return false;
    }

    public function getExitCode(): ?int
    {
        return 1;
    }

    public function isOutputDisabled(): bool
    {
        return true;
    }

    public function getWorkingDirectory(): ?string
    {
        return 'expected-working-directory';
    }
});
