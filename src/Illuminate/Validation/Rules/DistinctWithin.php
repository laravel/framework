<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DistinctWithin implements Rule
{
    protected int|\DateInterval $seconds;
    protected ?Request $request;

    /**
     * Constructor.
     *
     * @param  int|\DateInterval|array{int, 'milliseconds'|'seconds'|'minutes'|'hours'}  $seconds
     * @param  Request|null  $request  Optional, inject Request for testability
     */
    public function __construct(int|\DateInterval $seconds = 60, ?Request $request = null)
    {
        $this->seconds = $seconds instanceof \DateInterval ? $seconds->s : $seconds;
        $this->request = $request;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        // Use injected request if provided, otherwise use helper
        $request = $this->request ?? request();

        // Get client ID from session
        $clientId = $request->session()->getId();

        // Hash all request input
        $hash = sha1(json_encode($request->all()));

        // Build cache key
        $key = "validation_distinct_{$clientId}_{$hash}";

        if (Cache::has($key)) {
            return false;
        }

        // Store in cache for $seconds
        Cache::put($key, true, $this->seconds);

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return "Duplicate submission detected within {$this->seconds} seconds.";
    }
}
