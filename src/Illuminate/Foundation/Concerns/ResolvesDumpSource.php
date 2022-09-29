<?php

namespace Illuminate\Foundation\Concerns;

trait ResolvesDumpSource
{
    /**
     * The source resolver.
     *
     * @var (callable(): (array{0: string, 1: string, 2: int|null}|null))|null
     */
    protected static $dumpSourceResolver;

    /**
     * Resolve the source of the dump call.
     *
     * @return array{0: string, 1: string, 2: int|null}|null
     */
    public function resolveDumpSource()
    {
        if (static::$dumpSourceResolver) {
            return call_user_func(static::$dumpSourceResolver);
        }

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 20);

        $sourceKey = null;

        foreach ($trace as $traceKey => $traceFile) {
            if (isset($traceFile['file']) && str_ends_with(
                $traceFile['file'],
                'symfony/var-dumper/Resources/functions/dump.php'
            )) {
                $sourceKey = $traceKey + 1;

                break;
            }
        }

        if (is_null($sourceKey)) {
            return;
        }

        $file = $trace[$sourceKey]['file'] ?? null;
        $line = $trace[$sourceKey]['line'] ?? null;

        if (is_null($file) || is_null($line)) {
            return;
        }

        $relativeFile = $file;

        if ($this->isCompiledViewFile($file)) {
            $file = $this->getOriginalFileForCompiledView($file);
            $line = null;
        }

        if (str_starts_with($file, $this->basePath)) {
            $relativeFile = substr($file, strlen($this->basePath) + 1);
        }

        return [$file, $relativeFile, $line];
    }

    /**
     * Determine if the given file is a view compiled.
     *
     * @param  string  $file
     * @return bool
     */
    protected function isCompiledViewFile($file)
    {
        return str_starts_with($file, $this->compiledViewPath);
    }

    /**
     * Get the original view compiled file by the given compiled file.
     *
     * @param  string  $file
     * @return string
     */
    protected function getOriginalFileForCompiledView($file)
    {
        preg_match('/\/\*\*PATH\s(.*)\sENDPATH/', file_get_contents($file), $matches);

        if (isset($matches[1])) {
            $file = $matches[1];
        }

        return $file;
    }

    /**
     * Set the resolver that resolves the source of the dump call.
     *
     * @param  (callable(): (array{0: string, 1: string, 2: int|null}|null))|null  $callable
     * @return void
     */
    public static function resolveDumpSourceUsing($callable)
    {
        static::$dumpSourceResolver = $callable;
    }
}
