<?php

namespace Illuminate\Console;

interface ConfirmHandlerInterface
{
    /**
     * Return if the the console should ask to confirm by default.
     *
     * @param \Illuminate\Contracts\Foundation\Application $laravel
     * @return bool
     */
    public static function handle($laravel);

    /**
     * Return warning message for console confirm.
     *
     * @return string
     */
    public static function warning();
}
