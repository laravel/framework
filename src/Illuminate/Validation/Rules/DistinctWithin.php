<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Cache;

class DistinctWithin implements Rule
{
    protected int $seconds;

    public function __construct(int $seconds = 60)
    {
        $this->seconds = $seconds;
    }

    public function passes($attribute, $value)
    {
        $request = request();
        $clientId = $request->session()->getId();
        $hash = sha1(json_encode($request->all()));
        $key = "validation_distinct_{$clientId}_{$hash}";

        if (Cache::has($key)) {
            return false;
        }

        Cache::put($key, true, $this->seconds);

        return true;
    }

    public function message()
    {
        return "Duplicate submission detected within {$this->seconds} seconds.";
    }
}
