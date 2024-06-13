<?php

namespace Illuminate\Database\Middleware;

use Illuminate\Support\Facades\DB;

class Transactional
{
    /**
     * Start a new database transaction.
     *
     * @param  mixed  $passable
     * @param  callable  $next
     * @return mixed
     */
    public function handle($passable, $next)
    {
        return DB::transaction(fn () => $next($passable));
    }
}
