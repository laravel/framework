<?php

namespace Illuminate\Console;

use Illuminate\Contracts\Console\ConfirmHandler as ConfirmHandlerContract;

class ConfirmHandler implements ConfirmHandlerContract
{
    /**
     * Return if the the console should ask to confirm by default.
     *
     * @param \Illuminate\Contracts\Foundation\Application $laravel
     * @return bool
     */
    public static function handle($laravel)
    {
        return $laravel->environment() === 'production';
    }

    /**
     * Return warning message for console confirm.
     *
     * @return string
     */
    public static function warning()
    {
        return 'Application In Production!';
    }
}
