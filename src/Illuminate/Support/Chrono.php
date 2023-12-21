<?php

namespace Illuminate\Support;

use InvalidArgumentException;

class Chrono
{
    /**
     * Array to store each timestamp along with associated data.
     *
     * @var array
     */
    protected static array $stamps = [];

    /**
     * Allowed keys for sorting the stamps in stampsBy method.
     *
     * @var array
     */
    protected static array $stampsBy = ['relative', 'absolute', 'memory', 'time'];

    /**
     * Specifies the keys to include when dumping stamp data. If null, all keys are included.
     *
     * @var ?array
     */
    protected static ?array $dumpKeys = null;

    /**
     * Cache to store frequently accessed data to improve performance.
     *
     * @var array
     */
    protected static array $cache = [];

    /**
     * Resets the timestamps and cache. Optionally resets memory peak usage.
     *
     * @param  bool  $memory  Whether to reset memory peak usage.
     */
    public static function reset(bool $memory = false): void
    {
        static::$stamps = [];
        static::$cache = [];

        if ($memory && function_exists('memory_reset_peak_usage')) {
            memory_reset_peak_usage();
        }
    }

    /**
     * Sets the keys to be included when dumping the stamp data.
     *
     * @param  string|array|null  $dumpKeys  Keys to be dumped.
     */
    public static function dumpKeys(string|array|null $dumpKeys): void
    {
        static::$dumpKeys = $dumpKeys ? array_flip((array) $dumpKeys) : null;
    }

    /**
     * Creates a timestamp with performance data and optionally dumps it.
     *
     * @param  bool  $dump  Whether to dump the stamp data.
     * @param  mixed  $data  Additional data to be included in the stamp.
     * @return array The stamp data.
     */
    public static function stamp(bool $dump = false, mixed $data = null): array
    {
        $time = microtime(true);

        if ($previous = end(static::$stamps)) {
            unset($previous['previous']);

            $absolute = static::$stamps[0]['time'];
            $relative = $previous['time'];
        } else {
            $absolute = $time;
            $relative = $time;
        }

        static::$stamps[] = $stamp = static::debug() + [
            'time' => $time,
            'relative' => round($time - $relative, 5),
            'absolute' => round($time - $absolute, 5),
            'memory' => round(memory_get_peak_usage(true) / 1024 / 1024, 3),
            'data' => $data,
            'previous' => $previous,
        ];

        if ($dump) {
            static::dump($stamp);
        }

        return $stamp;
    }

    /**
     * Retrieves all stored stamps.
     *
     * @return array An array of all stamps.
     */
    public static function stamps(): array
    {
        return static::$stamps;
    }

    /**
     * Returns all stamps sorted by a specified key.
     *
     * @param  string  $key  The key to sort by.
     * @param  int  $limit  Limit the returned stamps
     * @return array Sorted array of stamps.
     *
     * @throws InvalidArgumentException If the provided key is invalid.
     */
    public static function stampsBy(string $key, int $limit = 0): array
    {
        if (! in_array($key, static::$stampsBy)) {
            throw new InvalidArgumentException(sprintf('Invalid Sort Key %s', $key));
        }

        $stamps = static::$stamps;

        usort($stamps, static fn ($a, $b) => $b[$key] <=> $a[$key]);

        return $limit ? array_slice($stamps, 0, $limit) : $stamps;
    }

    /**
     * Dumps the specified stamp data.
     *
     * @param  array  $stamp  The stamp to dump.
     */
    protected static function dump(array $stamp): void
    {
        if (static::$dumpKeys) {
            $stamp = array_intersect_key($stamp, static::$dumpKeys);
        }

        dump($stamp);
    }

    /**
     * Gets the file path relative to the base path.
     *
     * @param  ?string  $file  The file path to process.
     * @return string Relative file path.
     */
    protected static function file(?string $file): string
    {
        if ($file === null) {
            return '[PHP Kernel]';
        }

        return substr($file, strlen(static::$cache['base_path'] ??= base_path()));
    }

    /**
     * Collects and returns backtrace debug information.
     *
     * @return array Debug information array.
     */
    protected static function debug(): array
    {
        $debug = debug_backtrace(! DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 3);

        return [
            'file' => static::file($debug[1]['file'] ?? null),
            'line' => $debug[1]['line'] ?? null,
            'class' => $debug[2]['class'] ?? null,
            'function' => $debug[2]['function'] ?? null,
            'type' => $debug[2]['type'] ?? null,
        ];
    }
}
