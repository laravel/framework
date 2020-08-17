<?php

namespace Illuminate\Cookie\Middleware;

use Closure;
use Illuminate\Support\Facades\Session;
use Illuminate\Cookie\CookieValuePrefix;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter as EncrypterContract;

class EncryptCookies
{
    /**
     * The encrypter instance.
     *
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    protected $encrypter;

    /**
     * The names of the cookies that should not be encrypted.
     *
     * @var array
     */
    protected $except = [];

    /**
     * Indicates if the cookies should be serialized.
     *
     * @var bool
     */
    protected static $serialize = false;

    /**
     * Create a new CookieGuard instance.
     *
     * @param  \Illuminate\Contracts\Encryption\Encrypter  $encrypter
     * @return void
     */
    public function __construct(EncrypterContract $encrypter)
    {
        $this->encrypter = $encrypter;
    }

    /**
     * Disable encryption for the given cookie name(s).
     *
     * @param  string|array  $cookieName
     * @return void
     */
    public function disableFor($cookieName)
    {
        $this->except = array_merge($this->except, (array) $cookieName);
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        return $this->encrypt($next($this->decrypt($request)));
    }

    /**
     * Decrypt the cookies on the request.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @return \Symfony\Component\HttpFoundation\Request
     */
    protected function decrypt(Request $request)
    {
        foreach ($request->cookies as $key => $cookie) {
            if ($this->isDisabled($key)) {
                continue;
            }

            try {
                $decryptedValue = $this->decryptCookie($key, $cookie);

                $value = CookieValuePrefix::getVerifiedValue($key, $decryptedValue, $this->encrypter->getKey());

                if (empty($value) && $key === config('session.cookie') && Session::isValidId($decryptedValue)) {
                    $value = $decryptedValue;
                }

                $request->cookies->set($key, $value);
            } catch (DecryptException $e) {
                $request->cookies->set($key, null);
            }
        }

        return $request;
    }

    /**
     * Decrypt the given cookie and return the value.
     *
     * @param  string  $name
     * @param  string|array  $cookie
     * @return string|array
     */
    protected function decryptCookie($name, $cookie)
    {
        return is_array($cookie)
                        ? $this->decryptArray($cookie)
                        : $this->encrypter->decrypt($cookie, static::serialized($name));
    }

    /**
     * Decrypt an array based cookie.
     *
     * @param  array  $cookie
     * @return array
     */
    protected function decryptArray(array $cookie)
    {
        $decrypted = [];

        foreach ($cookie as $key => $value) {
            if (is_string($value)) {
                $decrypted[$key] = $this->encrypter->decrypt($value, static::serialized($key));
            }
        }

        return $decrypted;
    }

    /**
     * Encrypt the cookies on an outgoing response.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function encrypt(Response $response)
    {
        foreach ($response->headers->getCookies() as $cookie) {
            if ($this->isDisabled($cookie->getName())) {
                continue;
            }

            $prefix = '';

            if ($cookie->getName() !== 'XSRF-TOKEN') {
                $prefix = CookieValuePrefix::create($cookie->getName(), $this->encrypter->getKey());
            }

            $response->headers->setCookie($this->duplicate(
                $cookie, $this->encrypter->encrypt($prefix.$cookie->getValue(), static::serialized($cookie->getName()))
            ));
        }

        return $response;
    }

    /**
     * Duplicate a cookie with a new value.
     *
     * @param  \Symfony\Component\HttpFoundation\Cookie  $c
     * @param  mixed  $value
     * @return \Symfony\Component\HttpFoundation\Cookie
     */
    protected function duplicate(Cookie $c, $value)
    {
        return new Cookie(
            $c->getName(), $value, $c->getExpiresTime(), $c->getPath(),
            $c->getDomain(), $c->isSecure(), $c->isHttpOnly(), $c->isRaw(),
            $c->getSameSite()
        );
    }

    /**
     * Determine whether encryption has been disabled for the given cookie.
     *
     * @param  string $name
     * @return bool
     */
    public function isDisabled($name)
    {
        return in_array($name, $this->except);
    }

    /**
     * Determine if the cookie contents should be serialized.
     *
     * @param  string  $name
     * @return bool
     */
    public static function serialized($name)
    {
        return static::$serialize;
    }
}
