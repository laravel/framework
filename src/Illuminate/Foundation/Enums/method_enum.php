<?php
namespace Illuminate\Foundation\Enums;

use Exception;

enum method_enum: string {
    case GET = "GET";
    case POST = "POST";
    case PUT = "PUT";
    case DELETE = "DELETE";
    case OPTIONS = "OPTIONS";
    case PATCH = "PATCH";

    /**
     * @throws Exception
     */
    public static function get(string $method)
    {
        $method = self::tryfrom(strtoupper($method));
        return is_null($method) == true ? throw new Exception("Not Found Method!") : $method->value;
    }
}
