<?php

namespace Illuminate\Tests\Integration\Http\Middleware;

use Illuminate\Encryption\Encrypter;
use Illuminate\Http\Request;
use Orchestra\Testbench\TestCase;

class VerifyCsrfTokenExceptTest extends TestCase
{
    private $stub;
    private $request;

    protected function setUp(): void
    {
        parent::setUp();

        VerifyCsrfTokenExceptStub::except(['/globally/ignored']);
        $this->stub = new VerifyCsrfTokenExceptStub(app(), new Encrypter(Encrypter::generateKey('AES-128-CBC')));
        $this->request = Request::create('http://example.com/foo/bar', 'POST');
    }

    public function testItCanExceptPaths()
    {
        $this->assertMatchingExcept(['/foo/bar']);
        $this->assertMatchingExcept(['foo/bar']);
        $this->assertNonMatchingExcept(['/bar/foo']);
    }

    public function testPathsCanBeGloballyIgnored()
    {
        $this->request = Request::create('http://example.com/globally/ignored', 'POST');
        $this->assertMatchingExcept(['globally/ignored']);
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
