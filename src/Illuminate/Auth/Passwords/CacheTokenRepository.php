<?php

namespace Illuminate\Auth\Passwords;

use Illuminate\Cache\Repository;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Contracts\Hashing\Hasher as HasherContract;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CacheTokenRepository implements TokenRepositoryInterface
{
    /**
     * The Hasher implementation.
     */
    protected HasherContract $hasher;

    /**
     * The hashing key.
     */
    protected string $hashKey;

    /**
     * The number of seconds a token should last.
     */
    protected int|float $expires;

    /**
     * Minimum number of seconds before re-redefining the token.
     */
    protected int $throttle;

    /**
     * @var \Illuminate\Cache\Repository
     */
    private Repository $cache;

    /**
     * Create a new token repository instance.
     *
     * @param  \Illuminate\Cache\Repository  $cache
     * @param  \Illuminate\Contracts\Hashing\Hasher  $hasher
     * @param  string  $hashKey
     * @param  int  $expires
     * @param  int  $throttle
     */
    public function __construct(
        Repository     $cache,
        HasherContract $hasher,
        string         $hashKey,
        int            $expires = 60,
        int            $throttle = 60
    ) {
        $this->cache = $cache;
        $this->hasher = $hasher;
        $this->hashKey = $hashKey;
        $this->expires = $expires * 60;
        $this->throttle = $throttle;
    }

    /**
     * Create a new token.
     *
     * @param  \Illuminate\Contracts\Auth\CanResetPassword  $user
     * @return string
     */
    public function create(CanResetPasswordContract $user)
    {
        $email = $user->getEmailForPasswordReset();

        $this->cache->forget($email);

        $token = hash_hmac('sha256', Str::random(40), $this->hashKey);

        $this->cache->put($email, [$token, Carbon::now()], $this->expires);

        return $token;
    }

    /**
     * Determine if a token record exists and is valid.
     *
     * @param  \Illuminate\Contracts\Auth\CanResetPassword  $user
     * @param  string  $token
     * @return bool
     */
    public function exists(CanResetPasswordContract $user, #[\SensitiveParameter] $token)
    {
        [$record, $createdAt] = $this->cache->get($user->getEmailForPasswordReset());

        return $record
            && ! $this->tokenExpired($createdAt)
            && $this->hasher->check($token, $record);
    }

    /**
     * Determine if the token has expired.
     *
     * @param  string  $createdAt
     * @return bool
     */
    protected function tokenExpired($createdAt)
    {
        return Carbon::parse($createdAt)->addSeconds($this->expires)->isPast();
    }

    /**
     * Determine if the given user recently created a password reset token.
     *
     * @param  \Illuminate\Contracts\Auth\CanResetPassword  $user
     * @return bool
     */
    public function recentlyCreatedToken(CanResetPasswordContract $user)
    {
        [$record, $createdAt] = $this->cache->get($user->getEmailForPasswordReset());

        return $record && $this->tokenRecentlyCreated($createdAt);
    }

    /**
     * Determine if the token was recently created.
     *
     * @param  string  $createdAt
     * @return bool
     */
    protected function tokenRecentlyCreated($createdAt)
    {
        if ($this->throttle <= 0) {
            return false;
        }

        return Carbon::parse($createdAt)->addSeconds(
            $this->throttle
        )->isFuture();
    }

    /**
     * Delete a token record.
     *
     * @param  \Illuminate\Contracts\Auth\CanResetPassword  $user
     * @return void
     */
    public function delete(CanResetPasswordContract $user)
    {
        $this->cache->forget($user->getEmailForPasswordReset());
    }

    /**
     * Delete expired tokens.
     *
     * @return void
     */
    public function deleteExpired()
    {
        return;
    }
}
