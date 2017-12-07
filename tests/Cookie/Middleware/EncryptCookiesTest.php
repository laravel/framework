<?php

namespace Illuminate\Tests\Cookie\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Router;
use PHPUnit\Framework\TestCase;
use Illuminate\Cookie\CookieJar;
use Illuminate\Events\Dispatcher;
use Illuminate\Routing\Controller;
use Illuminate\Container\Container;
use Illuminate\Encryption\Encrypter;
use Symfony\Component\HttpFoundation\Cookie;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Contracts\Encryption\Encrypter as EncrypterContract;

class EncryptCookiesTest extends TestCase
{
    /**
     * @var Router
     */
    protected $router;

    protected $setCookiePath = 'cookie/set';
    protected $queueCookiePath = 'cookie/queue';

    public function setUp()
    {
        parent::setUp();

        $container = new Container;
        $container->singleton(EncrypterContract::class, function () {
            return new Encrypter(str_repeat('a', 16));
        });

        $this->router = new Router(new Dispatcher, $container);
    }

    public function testSetCookieEncryption()
    {
        $this->router->get($this->setCookiePath, [
            'middleware' => 'Illuminate\Tests\Cookie\Middleware\EncryptCookiesTestMiddleware',
            'uses' => 'Illuminate\Tests\Cookie\Middleware\EncryptCookiesTestController@setCookies',
        ]);

        $response = $this->router->dispatch(Request::create($this->setCookiePath, 'GET'));

        $cookies = $response->headers->getCookies();
        $this->assertCount(2, $cookies);
        $this->assertEquals('encrypted_cookie', $cookies[0]->getName());
        $this->assertNotEquals('value', $cookies[0]->getValue());
        $this->assertEquals('unencrypted_cookie', $cookies[1]->getName());
        $this->assertEquals('value', $cookies[1]->getValue());
    }

    public function testQueuedCookieEncryption()
    {
        $this->router->get($this->queueCookiePath, [
            'middleware' => ['Illuminate\Tests\Cookie\Middleware\EncryptCookiesTestMiddleware', 'Illuminate\Tests\Cookie\Middleware\AddQueuedCookiesToResponseTestMiddleware'],
            'uses' => 'Illuminate\Tests\Cookie\Middleware\EncryptCookiesTestController@queueCookies',
        ]);

        $response = $this->router->dispatch(Request::create($this->queueCookiePath, 'GET'));

        $cookies = $response->headers->getCookies();
        $this->assertCount(2, $cookies);
        $this->assertEquals('encrypted_cookie', $cookies[0]->getName());
        $this->assertNotEquals('value', $cookies[0]->getValue());
        $this->assertEquals('unencrypted_cookie', $cookies[1]->getName());
        $this->assertEquals('value', $cookies[1]->getValue());
    }
}

class EncryptCookiesTestController extends Controller
{
    public function setCookies()
    {
        $response = new Response;
        $response->headers->setCookie(new Cookie('encrypted_cookie', 'value'));
        $response->headers->setCookie(new Cookie('unencrypted_cookie', 'value'));

        return $response;
    }

    public function queueCookies()
    {
        return new Response;
    }
}

class EncryptCookiesTestMiddleware extends EncryptCookies
{
    protected $except = [
        'unencrypted_cookie',
    ];
}

class AddQueuedCookiesToResponseTestMiddleware extends AddQueuedCookiesToResponse
{
    public function __construct()
    {
        $cookie = new CookieJar;
        $cookie->queue(new Cookie('encrypted_cookie', 'value'));
        $cookie->queue(new Cookie('unencrypted_cookie', 'value'));

        $this->cookies = $cookie;
    }
}
