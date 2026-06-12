<?php

namespace Illuminate\Support\Contracts;

interface NodePackageManager
{
    public static function matches(): bool;

    public function getRunCommand(string $command): string;

    public function getExecCommand(string $command): string;
}
