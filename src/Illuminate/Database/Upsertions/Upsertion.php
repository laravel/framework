<?php

namespace Illuminate\Database\Upsertions;

abstract class Upsertion
{
    abstract public function shouldRun(): bool;

    abstract public function run(): void;
}
