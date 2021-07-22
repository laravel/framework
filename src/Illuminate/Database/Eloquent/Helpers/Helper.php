<?php
namespace Illuminate\Database\Eloquent\Helpers;
use Closure;

/**
 * Class Helper
 *
 * The purpose of this class is create a helper functions to prevent the repetition of the code
 * Every function should achieve the ( single responsibility ) principle
 */
class Helper {

    /**
     * Check if it's a closure or not
     *
     * @param $var
     * @return bool
     */
    public static function is_closure($var): bool
    {
        return $var instanceof Closure;
    }
}
