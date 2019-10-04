<?php

namespace Illuminate\Contracts\Foundation\Testing;

interface DatabaseTransactable
{
    /**
     * Handle database transactions on the specified connections.
     *
     * @return void
     */
    public function beginDatabaseTransaction();
}
