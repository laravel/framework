<?php

namespace Illuminate\Foundation\Exceptions\Renderer;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Laravel\Octane\Events\RequestReceived;
use Laravel\Octane\Events\RequestTerminated;
use Laravel\Octane\Events\TaskReceived;
use Laravel\Octane\Events\TickReceived;

class Listener
{
    /** Safety numeric cap (entries count) */
    private const HARD_CAP = 100;

    /** Total in-memory budget for stored queries (in bytes) */
    private const MAX_BUDGET_BYTES = 262144; // 256KB

    /** Byte-accurate limits (including the ellipsis) */
    private const MAX_SQL_BYTES = 2001;
    private const MAX_BINDING_BYTES = 513;

    /** Ellipsis used when clipping strings */
    private const ELLIPSIS = '…';


    /**
     * The queries that have been captured for the local debug page.
     *
     * @var array<int, array{
     *   connectionName: string,
     *   time: float,
     *   sql: string,
     *   bindings: array,
     *   truncated?: bool
     * }>
     */
    protected $queries = [];

    /** Current total estimated bytes of the in-memory buffer. */
    protected int $totalBytes = 0;

    /**
     * Register the appropriate listeners on the given event dispatcher.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function registerListeners(Dispatcher $events)
    {
        $events->listen(QueryExecuted::class, $this->onQueryExecuted(...));

        $events->listen([JobProcessing::class, JobProcessed::class], function () {
            $this->clearQueries();
        });

        if (isset($_SERVER['LARAVEL_OCTANE'])) {
            $events->listen([RequestReceived::class, TaskReceived::class, TickReceived::class, RequestTerminated::class], function () {
                $this->clearQueries();
            });
        }
    }

    /**
     * Clear the stored queries and reset the byte counter. Encourages GC.
     *
     * @return void
     */
    protected function clearQueries()
    {
        if (! empty($this->queries)) {
            foreach ($this->queries as $i => $_) {
                $this->queries[$i] = null;
            }
            $this->queries = [];
        }
        $this->totalBytes = 0;

        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
        if (function_exists('gc_mem_caches')) {
            gc_mem_caches();
        }
    }

    /**
     * Estimate the byte size of a single entry (roughly).
     *
     * @param  array{sql?:string, bindings?:array}  $entry
     * @return int
     */
    protected function bytesOf(array $entry): int
    {
        $bytes = strlen($entry['sql'] ?? '');

        if (! empty($entry['bindings'])) {
            foreach ($entry['bindings'] as $v) {
                if (is_string($v)) {
                    $bytes += strlen($v);
                } elseif (is_scalar($v)) {
                    $bytes += strlen((string) $v);
                }
            }
        }

        // overhead بسيط
        return $bytes + 64;
    }

    /**
     * Clip SQL and string bindings to byte-accurate limits.
     *
     * @param  array{sql?:string, bindings?:array}  $entry
     * @return array{sql?:string, bindings?:array, truncated?:bool}
     */
    protected function truncateEntry(array $entry): array
    {
        if (isset($entry['sql'])) {
            $orig = $entry['sql'];
            $entry['sql'] = $this->clipToBytes((string) $entry['sql'], self::MAX_SQL_BYTES);
            if ($entry['sql'] !== $orig) {
                $entry['truncated'] = true;
            }
        }

        if (! empty($entry['bindings'])) {
            foreach ($entry['bindings'] as $k => $v) {
                if (is_string($v)) {
                    $orig = $v;
                    $entry['bindings'][$k] = $this->clipToBytes($v, self::MAX_BINDING_BYTES);
                    if ($entry['bindings'][$k] !== $orig) {
                        $entry['truncated'] = true;
                    }
                }
            }
        }

        return $entry;
    }


    /**
     * Admit a new entry into the buffer, evicting oldest until under budget.
     *
     * @param  array{connectionName:string, time:float, sql:string, bindings:array}  $entry
     * @return void
     */
    protected function admit(array $entry): void
    {
        // Initial clipping to avoid extremely large entries
        $entry = $this->truncateEntry($entry);
        $size  = $this->bytesOf($entry);

        // Evict oldest entries until the budget allows admitting the new one
        while (! empty($this->queries) && ($this->totalBytes + $size) > self::MAX_BUDGET_BYTES) {
            $old = array_shift($this->queries);
            $this->totalBytes -= $this->bytesOf($old);
        }

        // Safety numeric cap (belt-and-suspenders)
        if (count($this->queries) >= self::HARD_CAP) {
            $old = array_shift($this->queries);
            $this->totalBytes -= $this->bytesOf($old);
        }

        $this->queries[] = $entry;
        $this->totalBytes += $size;
    }

    private function clipToBytes(string $s, int $maxBytes): string
    {
        if (strlen($s) <= $maxBytes) {
            return $s;
        }
        $ellipsisBytes = strlen(self::ELLIPSIS);
        $cut = max(0, $maxBytes - $ellipsisBytes);
        return substr($s, 0, $cut) . self::ELLIPSIS;
    }

    /**
     * Returns the queries that have been executed.
     *
     * @return array<int, array{
     *   connectionName: string,
     *   time: float,
     *   sql: string,
     *   bindings: array,
     *   truncated?: bool
     * }>
     */
    public function queries()
    {
        return $this->queries;
    }


    /**
     * Listens for the query executed event.
     *
     * @param  \Illuminate\Database\Events\QueryExecuted  $event
     * @return void
     */
    public function onQueryExecuted(QueryExecuted $event)
    {
        if (! config('app.debug')) {
            return;
        }

        $entry = [
            'connectionName' => $event->connectionName,
            'time'           => $event->time,
            'sql'            => (string) $event->sql,
            'bindings'       => $event->connection->prepareBindings($event->bindings),
        ];

        $this->admit($entry);
    }
}
