<?php

namespace Illuminate\Foundation\Http;

use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Cookie;
use function config;

class MaintenanceModeBypassCookie
{
    /**
     * Create a new maintenance mode bypass cookie.
     *
     * @param  string  $key
     * @return \Symfony\Component\HttpFoundation\Cookie
     */
    public static function create(string $key)
    {
        $expiresAt = Carbon::now()->addHours(12);
        $config = config('session');

        return new Cookie(
            'laravel_maintenance',
            base64_encode(json_encode([
                'expires_at' => $expiresAt->getTimestamp(),
                'mac' => hash_hmac('SHA256', $expiresAt->getTimestamp(), $key),
            ])),
            $expiresAt,
            $config['path'] ?? '/',
            $config['domain'] ?? null,
            $config['secure'] ?? null,
            $config['httpOnly'] ?? false,
            $config['raw'] ?? false,
            $config['same_site'] ?? null
        );
    }

    /**
     * Determine if the given maintenance mode bypass cookie is valid.
     *
     * @param  string  $cookie
     * @param  string  $key
     * @return bool
     */
    public static function isValid(string $cookie, string $key)
    {
        $payload = json_decode(base64_decode($cookie), true);

        return is_array($payload) &&
            is_numeric($payload['expires_at'] ?? null) &&
            isset($payload['mac']) &&
            hash_equals(hash_hmac('SHA256', $payload['expires_at'], $key), $payload['mac']) &&
            (int) $payload['expires_at'] >= Carbon::now()->getTimestamp();
    }
}
