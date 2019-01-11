<?php

namespace Illuminate\Tests\Integration\Http\Middleware;

use Illuminate\Http\Request;
use Orchestra\Testbench\TestCase;

class VerifyCsrfTokenExceptTest extends TestCase
{
    private $stub;
    private $request;

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.encryption', [
            'driver' => 'openssl',
            'cipher' => 'AES-256-CBC',
            'key'    => 'base64:IUHRqAQ99pZ0A1MPjbuv1D6ff3jxv0GIvS2qIW4JNU4=',
        ]);
    }

    public function setUp()
    {
        parent::setUp();

        $e = $this->app->make('encrypter');

        $this->stub = new VerifyCsrfTokenExceptStub(app(), $e);
        $this->request = Request::create('http://example.com/foo/bar', 'POST');
    }

    public function testItCanExceptPaths()
    {
        $this->assertMatchingExcept(['/foo/bar']);
        $this->assertMatchingExcept(['foo/bar']);
        $this->assertNonMatchingExcept(['/bar/foo']);
    }

    public function testItCanExceptWildcardPaths()
    {
        $this->assertMatchingExcept(['/foo/*']);
        $this->assertNonMatchingExcept(['/bar*']);
    }

    public function testItCanExceptFullUrlPaths()
    {
        $this->assertMatchingExcept(['http://example.com/foo/bar']);
        $this->assertMatchingExcept(['http://example.com/foo/bar/']);

        $this->assertNonMatchingExcept(['https://example.com/foo/bar/']);
        $this->assertNonMatchingExcept(['http://foobar.com/']);
    }

    public function testItCanExceptFullUrlWildcardPaths()
    {
        $this->assertMatchingExcept(['http://example.com/*']);
        $this->assertMatchingExcept(['*example.com*']);

        $this->request = Request::create('https://example.com', 'POST');
        $this->assertMatchingExcept(['*example.com']);
    }

    private function assertMatchingExcept(array $except, $bool = true)
    {
        $this->assertSame($bool, $this->stub->setExcept($except)->checkInExceptArray($this->request));
    }

    private function assertNonMatchingExcept(array $except)
    {
        return $this->assertMatchingExcept($except, false);
    }
}
