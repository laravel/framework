<?php

namespace Illuminate\Queue\Middleware;

use Illuminate\Support\Facades\DB;

class Transactional
{
    /**
     * Process the job.
     *
     * @param  mixed  $job
     * @param  callable  $next
     * @return mixed
     */
    public function handle($job, $next)
    {
        return DB::transaction(fn () => $next($job));
    }
}
