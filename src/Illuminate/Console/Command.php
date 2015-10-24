<?php

namespace Illuminate\Console;

use Illuminate\Contracts\Foundation\Application as LaravelApplication;

class Command extends BaseCommand
{
    /**
     * The Laravel application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $laravel;

    /**
     * Get the Laravel application instance.
     *
     * @return \Illuminate\Contracts\Foundation\Application
     */
    public function getLaravel()
    {
        return $this->laravel;
    }

    /**
     * Set the Laravel application instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $laravel
     * @return void
     */
    public function setLaravel(LaravelApplication $laravel)
    {
        $this->laravel = $this->container = $laravel;
    }
}
