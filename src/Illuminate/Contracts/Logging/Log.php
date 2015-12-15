<?php

namespace Illuminate\Contracts\Logging;

use Psr\Log\LoggerInterface;

interface Log extends LoggerInterface
{
    /**
     * Register a file log handler.
     *
     * @param  string  $path
     * @param  string  $level
     * @return void
     */
    public function useFiles($path, $level = 'debug');

    /**
     * Register a daily file log handler.
     *
     * @param  string  $path
     * @param  int     $days
     * @param  string  $level
     * @return void
     */
    public function useDailyFiles($path, $days = 0, $level = 'debug');
}
