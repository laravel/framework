<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Hash Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default hash driver that will be used to hash
    | passwords for your application. By default, the bcrypt algorithm is
    | used for broad compatibility; however, you may wish to use a more modern
    | algorithm such as Argon2id when your environment supports it.
    |
    | Argon2id is recommended for new applications as it provides improved
    | resistance against GPU-based and side-channel attacks due to its
    | memory-hard design.
    |
    | Supported: "bcrypt", "argon", "argon2id"
    |
    */

    'driver' => env('HASH_DRIVER', 'bcrypt'),

    /*
    |--------------------------------------------------------------------------
    | Bcrypt Options
    |--------------------------------------------------------------------------
    |
    | Here you may specify the configuration options that should be used when
    | passwords are hashed using the Bcrypt algorithm. This will allow you
    | to control the amount of time it takes to hash the given password.
    |
    | Bcrypt remains a secure and compatible choice, especially for legacy
    | systems or environments where Argon2 is not available.
    |
    */

    'bcrypt' => [
        'rounds' => env('BCRYPT_ROUNDS', 12),
        'verify' => env('HASH_VERIFY', true),
        'limit' => env('BCRYPT_LIMIT', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Argon Options
    |--------------------------------------------------------------------------
    |
    | Here you may specify the configuration options that should be used when
    | passwords are hashed using the Argon or Argon2id algorithms.
    |
    | Argon2id is generally recommended for modern applications as it offers
    | better protection against brute-force attacks by combining CPU and
    | memory cost factors.
    |
    | Note: Argon2 algorithms are more resource-intensive than bcrypt. You
    | should tune these values based on your application's performance and
    | infrastructure constraints.
    |
    */

    'argon' => [
        'memory' => env('ARGON_MEMORY', 65536),
        'threads' => env('ARGON_THREADS', 1),
        'time' => env('ARGON_TIME', 4),
        'verify' => env('HASH_VERIFY', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rehash On Login
    |--------------------------------------------------------------------------
    |
    | Setting this option to true will tell Laravel to automatically rehash
    | the user's password during login if the configured work factor or
    | algorithm has changed, allowing graceful upgrades of existing hashes.
    |
    */

    'rehash_on_login' => true,

];
