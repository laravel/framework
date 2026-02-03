<?php

namespace Illuminate\Database\Query\Listeners;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Log;

class DetectsNPlusOneQueries
{
    /**
     * The detected queries keyed by normalized SQL.
     *
     * @var array<string, array{count: int, stack_trace: array|null}>
     */
    protected $queries = [];

    /**
     * Handle the event.
     *
     * @param  \Illuminate\Database\Events\QueryExecuted  $event
     * @return void
     */
    public function handle(QueryExecuted $event)
    {
        if (! $this->shouldDetect()) {
            return;
        }

        $normalizedSql = $this->normalizeSql($event->sql);

        if (! isset($this->queries[$normalizedSql])) {
            $this->queries[$normalizedSql] = [
                'count' => 0,
                'stack_trace' => null,
            ];
        }

        $count = ++$this->queries[$normalizedSql]['count'];

        if ($this->queries[$normalizedSql]['stack_trace'] === null) {
            $this->queries[$normalizedSql]['stack_trace'] = $this->captureStackTrace();
        }

        // Simple heuristic: if the same normalized query is executed more than
        // a small threshold within a single request, treat it as a possible N+1.
        if ($count === 3) {
            Log::warning('Possible N+1 query detected.', [
                'normalized_sql' => $normalizedSql,
                'count' => $count,
            ]);
        }
    }

    /**
     * Determine if N+1 query detection should run.
     *
     * @return bool
     */
    protected function shouldDetect()
    {
        if (! function_exists('app') || ! function_exists('config')) {
            return false;
        }

        try {
            return app()->isLocal()
                && config('app.debug') === true
                && config('database.detect_n_plus_one', false) === true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Normalize the given SQL string.
     *
     * @param  string  $sql
     * @return string
     */
    protected function normalizeSql($sql)
    {
        // Collapse all whitespace and lowercase the SQL so that
        // semantically identical queries map to the same key.
        $sql = preg_replace('/\s+/', ' ', trim($sql));

        return strtolower($sql ?? '');
    }

    /**
     * Capture a lightweight stack trace.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function captureStackTrace()
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        return array_map(function ($frame) {
            return [
                'file' => $frame['file'] ?? null,
                'line' => $frame['line'] ?? null,
                'function' => $frame['function'] ?? null,
                'class' => $frame['class'] ?? null,
            ];
        }, $trace);
    }

    /**
     * Get the detected queries for the current request.
     *
     * @return array<string, array{count: int, stack_trace: array|null}>
     */
    public function detected()
    {
        return $this->queries;
    }
}

