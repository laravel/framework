<?php

namespace Illuminate\Cookie;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Cookie;
use Illuminate\Contracts\Cookie\QueueingFactory as JarContract;

class CookieJar implements JarContract
{
    /**
     * The default path (if specified).
     *
     * @var string
     */
    protected $path = '/';

    /**
     * The default domain (if specified).
     *
     * @var string
     */
    protected $domain;

    /**
     * The default secure setting (defaults to false).
     *
     * @var bool
     */
    protected $secure = false;

    /**
     * The default SameSite option (if specified).
     *
     * @var string
     */
    protected $sameSite;

    /**
     * All of the cookies queued for sending.
     *
     * @var array
     */
    protected $queued = [];

    /**
     * Create a new cookie instance.
     *
     * @param  string       $name
     * @param  string       $value
     * @param  int          $minutes
     * @param  string       $path
     * @param  string       $domain
     * @param  bool         $secure
     * @param  bool         $httpOnly
     * @param  bool         $raw
     * @param  string|null  $sameSite
     * @return \Symfony\Component\HttpFoundation\Cookie
     */
    public function make($name, $value, $minutes = 0, $path = null, $domain = null, $secure = false, $httpOnly = true, $raw = false, $sameSite = null)
    {
        list($path, $domain, $secure, $sameSite) = $this->getPathAndDomain($path, $domain, $secure, $sameSite);

        $time = ($minutes == 0) ? 0 : Carbon::now()->getTimestamp() + ($minutes * 60);

        return new Cookie($name, $value, $time, $path, $domain, $secure, $httpOnly, $raw, $sameSite);
    }

    /**
     * Create a cookie that lasts "forever" (five years).
     *
     * @param  string       $name
     * @param  string       $value
     * @param  string       $path
     * @param  string       $domain
     * @param  bool         $secure
     * @param  bool         $httpOnly
     * @param  bool         $raw
     * @param  string|null  $sameSite
     * @return \Symfony\Component\HttpFoundation\Cookie
     */
    public function forever($name, $value, $path = null, $domain = null, $secure = false, $httpOnly = true, $raw = false, $sameSite = null)
    {
        return $this->make($name, $value, 2628000, $path, $domain, $secure, $httpOnly, $raw, $sameSite);
    }

    /**
     * Expire the given cookie.
     *
     * @param  string  $name
     * @param  string  $path
     * @param  string  $domain
     * @return \Symfony\Component\HttpFoundation\Cookie
     */
    public function forget($name, $path = null, $domain = null)
    {
        return $this->make($name, null, -2628000, $path, $domain);
    }

    /**
     * Determine if a cookie has been queued.
     *
     * @param  string  $key
     * @return bool
     */
    public function hasQueued($key)
    {
        return ! is_null($this->queued($key));
    }

    /**
     * Get a queued cookie instance.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return \Symfony\Component\HttpFoundation\Cookie
     */
    public function queued($key, $default = null)
    {
        return Arr::get($this->queued, $key, $default);
    }

    /**
     * Queue a cookie to send with the next response.
     *
     * @param  array  $parameters
     * @return void
     */
    public function queue(...$parameters)
    {
        if (head($parameters) instanceof Cookie) {
            $cookie = head($parameters);
        } else {
            $cookie = call_user_func_array([$this, 'make'], $parameters);
        }

        $this->queued[$cookie->getName()] = $cookie;
    }

    /**
     * Remove a cookie from the queue.
     *
     * @param  string  $name
     * @return void
     */
    public function unqueue($name)
    {
        unset($this->queued[$name]);
    }

    /**
     * Get the path and domain, or the default values.
     *
     * @param  string  $path
     * @param  string  $domain
     * @param  bool    $secure
     * @param  string  $sameSite
     * @return array
     */
    protected function getPathAndDomain($path, $domain, $secure = false, $sameSite = null)
    {
        return [$path ?: $this->path, $domain ?: $this->domain, $secure ?: $this->secure, $sameSite ?: $this->sameSite];
    }

    /**
     * Set the default path and domain for the jar.
     *
     * @param  string  $path
     * @param  string  $domain
     * @param  bool    $secure
     * @param  string  $sameSite
     * @return $this
     */
    public function setDefaultPathAndDomain($path, $domain, $secure = false, $sameSite = null)
    {
        list($this->path, $this->domain, $this->secure, $this->sameSite) = [$path, $domain, $secure, $sameSite];

        return $this;
    }

    /**
     * Get the cookies which have been queued for the next request.
     *
     * @return array
     */
    public function getQueuedCookies()
    {
        return $this->queued;
    }
}
