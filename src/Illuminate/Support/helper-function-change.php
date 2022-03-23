<?php

/*
 * Function description: Helper function to convert every 'on' / 'off' to boolean when using check inputs, input name must start with 'is_', Laravel 8.x
 * Call by reference
 * Created by VS code
 * User: Mohammed Alshobaki
 * Email: mohammed.alshobaki96@gmail.com
 * Date: 23/03/22
 * Time: 10.00 AM
 */

if (! function_exists('convertOnOffToBoolean')) {
    function convertOnOffToBoolean(&$data, $originalKeys)
    {
        foreach ($data as $key => $value):
            if (strtolower($value) == 'on') {
                $data[$key] = true;
            }
        endforeach;

        $differenceKeys = array_diff_key($originalKeys, $data);
        foreach ($differenceKeys as $key => $value):
            if (str_starts_with($key, 'is_')) {
                $data[$key] = false;
            }
        endforeach;
    }
}