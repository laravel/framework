<?php

namespace Illuminate\Support\Contracts;

interface NodePackageManager
{
    public static function isInUse(): bool;

    public function getRunCommand(string $command): string;

    public function getExecCommand(string $command): string;
}
