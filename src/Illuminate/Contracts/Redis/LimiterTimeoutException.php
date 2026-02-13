<?php

namespace Illuminate\Contracts\Redis;

use Illuminate\Cache\Limiters\LimiterTimeoutException as BaseLimiterTimeoutException;

class LimiterTimeoutException extends BaseLimiterTimeoutException
{
    //
}
