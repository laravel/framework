<?php

/**
 * Generates a message for when the auth.guards.$name is missing.
 *
 * @param string $name
 *
 * @return string
 */
return function ($name) {
    return preg_replace("/\n {8}/", "\n", "
        Auth guard [{$name}] is not defined.

        This is usually caused by a faulty configuration, in config/auth.php. You could try adding something like:

        return [
            // ...
            'guards' => [
                '{$name}' => [
                    'driver' => 'session',
                    'provider' => 'users',
                ],
            ],
        ];

        - Need more documentation? Check out: https://laravel.com/docs/5.3/authentication#adding-custom-guards
        - Having issues with this error? Go to: https://laracasts.com/discuss
    ");
};
