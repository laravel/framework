<?php

namespace Illuminate\Contracts\Cache;

interface RetrievesTTL
{
    public function ttlInSeconds(string $key): ?int;
}

