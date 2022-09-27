<?php

namespace Illuminate\Foundation\Concerns;

trait ResolvesDumpSource
{
    /**
     * The source resolver.
     *
     * @var (callable(): (array{0: string, 1: string, 2: int}|null))|null
     */
    protected static $dumpSourceResolver;

    /**
     * Resolve the source of the dump call.
     *
     * @return array{0: string, 1: string, 2: int}|null
     */
    public function resolveDumpSource()
    {
        if (static::$dumpSourceResolver) {
            return call_user_func(static::$dumpSourceResolver);
        }

        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 20);

        $file = $trace[7]['file'] ?? null;
        $line = $trace[7]['line'] ?? null;

        if (is_null($file) || is_null($line)) {
            return;
        }

        $relativeFile = $file;

        if (str_starts_with($file, $this->basePath)) {
            $relativeFile = substr($file, strlen($this->basePath) + 1);
        }

        return [$file, $relativeFile, $line];
    }

    /**
     * Set the resolver that resolves the source of the dump call.
     *
     * @param  (callable(): (array{0: string, 1: string, 2: int}|null))|null  $callable
     * @return void
     */
    public static function resolveDumpSourceUsing($callable)
    {
        static::$dumpSourceResolver = $callable;
    }
}
