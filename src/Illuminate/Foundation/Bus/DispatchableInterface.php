<?php

namespace Illuminate\Foundation\Bus;

use Illuminate\Support\Fluent;

interface DispatchableInterface
{
    public static function dispatch(...$arguments): PendingDispatch;
    public static function dispatchIf($boolean, ...$arguments): PendingDispatch|Fluent;
    public static function dispatchUnless($boolean, ...$arguments): PendingDispatch|Fluent;
    public static function dispatchSync(...$arguments): mixed;
    public static function dispatchNow(...$arguments): mixed;
    public static function dispatchAfterResponse(...$arguments): mixed;
    public static function withChain($chain): PendingChain;
}
