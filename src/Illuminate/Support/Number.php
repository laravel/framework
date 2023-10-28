<?php

namespace Illuminate\Support;

use Illuminate\Support\Traits\Macroable;

class Number
{
    use Macroable;

    /**
     * Format the number of bytes to a human-readable string.
     *
     * @param  int  $bytes
     * @param  int  $precision
     * @return string
     */
    public static function bytesToHuman($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

        for ($i = 0; ($bytes / 1024) > 0.9 && ($i < count($units) - 1); $i++) {
            $bytes /= 1024;
        }

        return sprintf('%s %s', round($bytes, $precision), $units[$i]);
    }
}
