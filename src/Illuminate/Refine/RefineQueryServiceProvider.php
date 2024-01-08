<?php

namespace Illuminate\Refine;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\ServiceProvider;

class RefineQueryServiceProvider extends ServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        RefineQuery::setResolver($this->app);
    }
}
