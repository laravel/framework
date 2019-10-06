<?php

namespace Illuminate\Contracts\Foundation\Testing;

interface DatabaseRefreshable
{
    /**
     * Define hooks to migrate the database before and after each test.
     *
     * @return void
     */
    public function refreshDatabase();
}
