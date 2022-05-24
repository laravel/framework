<?php

namespace Illuminate\Tests\Testing\Fluent;

use BadMethodCallException;
use Illuminate\Testing\Fluent\AssertableUri;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;

class AssertableUriTest extends TestCase
{
    /** @var \Illuminate\Testing\Fluent\AssertableUri */
    protected $assert;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assert = new AssertableUri('https://username:password@hostname.com:8080/oauth2/v2.0?foo=bar#anchor');
    }

    public function testAssertHasFragment()
    {
        $this->assert->hasFragment();
    }

    public function testAssertHasFragmentFailsWhenComponentMissing()
    {
        $assert = new AssertableUri('https://foo.bar');

        $this->expectAssertionException('URI component [Fragment] does not exist.');

        $assert->hasFragment();
    }

    public function testAssertHasHost()
    {
        $this->assert->hasHost();
    }

    public function testAssertHasHostFailsWhenComponentMissing()
    {
        $assert = new AssertableUri('?foo=bar');

        $this->expectAssertionException('URI component [Host] does not exist.');

        $assert->hasHost();
    }

    public function testAssertHasPass()
    {
        $this->assert->hasPass();
    }

    public function testAssertHasPassFailsWhenComponentMissing()
    {
        $assert = new AssertableUri('https://foo.bar');

        $this->expectAssertionException('URI component [Pass] does not exist.');

        $assert->hasPass();
    }

    public function testAssertHasPath()
    {
        $this->assert->hasPath();
    }

    public function testAssertHasPathFailsWhenComponentMissing()
    {
        $assert = new AssertableUri('https://foo.bar');

        $this->expectAssertionException('URI component [Path] does not exist.');

        $assert->hasPath();
    }

    public function testAssertHasPort()
    {
        $this->assert->hasPort();
    }

    public function testAssertHasPortFailsWhenComponentMissing()
    {
        $assert = new AssertableUri('https://foo.bar');

        $this->expectAssertionException('URI component [Port] does not exist.');

        $assert->hasPort();
    }

    public function testAssertHasSpecificQuery()
    {
        $this->assert->hasQuery('foo');
    }

    public function testAssertHasQueryFailsWhenKeyMissing()
    {
        $this->expectAssertionException('Query [baz] does not exist.');

        $this->assert->hasQuery('baz');
    }

    public function testAssertHasNestedQuery()
    {
        $assert = new AssertableUri('https://foo.com?user[name]=Taylor&user[id]=1');

        $assert->hasQuery('user');
        $assert->hasQuery('user.name');
        $assert->hasQuery('user.id');
    }

    public function testAssertHasQueryFailsWhenNestedKeyMissing()
    {
        $assert = new AssertableUri('https://foo.com?user[name]=Taylor&user[id]=1');

        $this->expectAssertionException('Query [user.another] does not exist.');

        $assert->hasQuery('user.another');
    }

    public function testAssertHasScheme()
    {
        $this->assert->hasScheme();
    }

    public function testAssertHasSchemeFailsWhenComponentMissing()
    {
        $assert = new AssertableUri('foo.bar');

        $this->expectAssertionException('URI component [Scheme] does not exist.');

        $assert->hasScheme();
    }

    public function testAssertHasUser()
    {
        $this->assert->hasUser();
    }

    public function testAssertHasUserFailsWhenComponentMissing()
    {
        $assert = new AssertableUri('https://foo.bar');

        $this->expectAssertionException('URI component [User] does not exist.');

        $assert->hasUser();
    }

    public function testAssertWhereHost()
    {
        $this->assert->whereHost('hostname.com');
    }

    public function testAssertWhereHostFailsWhenDoesNotMatchValue()
    {
        $this->expectAssertionException('URI component [Host] does not match the expected value.');

        $this->assert->whereHost('incorrect');
    }

    public function testAssertWhereScheme()
    {
        $this->assert->whereScheme('https');
    }

    public function testAssertWhereSchemeFailsWhenDoesNotMatchValue()
    {
        $this->expectAssertionException('URI component [Scheme] does not match the expected value.');

        $this->assert->whereScheme('http');
    }

    public function testAssertWherePort()
    {
        $this->assert->wherePort(8080);
    }

    public function testAssertWherePortFailsWhenDoesNotMatchValue()
    {
        $this->expectAssertionException('URI component [Port] does not match the expected value.');

        $this->assert->wherePort(8000);
    }

    public function testAssertWhereUser()
    {
        $this->assert->whereUser('username');
    }

    public function testAssertWhereUserFailsWhenDoesNotMatchValue()
    {
        $this->expectAssertionException('URI component [User] does not match the expected value.');

        $this->assert->whereUser('incorrect');
    }

    public function testAssertWherePass()
    {
        $this->assert->wherePass('password');
    }

    public function testAssertWherePassFailsWhenDoesNotMatchValue()
    {
        $this->expectAssertionException('URI component [Pass] does not match the expected value.');

        $this->assert->wherePass('incorrect');
    }

    public function testAssertWherePath()
    {
        $this->assert->wherePath('/oauth2/v2.0');
    }

    public function testAssertWherePathFailsWhenDoesNotMatchValue()
    {
        $this->expectAssertionException('URI component [Path] does not match the expected value.');

        $this->assert->wherePath('incorrect');
    }

    public function testAssertWhereQuery()
    {
        $this->assert->whereQuery('foo', 'bar');
    }

    public function testAssertWhereQueryFailsWhenDoesNotMatchValue()
    {
        $this->expectAssertionException('Query [foo] does not match the expected value.');

        $this->assert->whereQuery('foo', 'baz');
    }

    public function testAssertWhereQueryFailsWhenComponentMissing()
    {
        $assert = new AssertableUri('https://foo.bar');

        $this->expectAssertionException('URI component [Query] does not exist.');

        $assert->whereQuery('foo', 'bar');
    }

    public function testAssertRawWhereQuery()
    {
        $assert = new AssertableUri('https://foo.bar?name=Taylor&id=1');

        $assert->whereQuery('name=Taylor&id=1');
    }

    public function testAssertWhereNestedQuery()
    {
        $assert = new AssertableUri('https://foo.com?user[name]=Taylor&user[id]=1');

        $assert->whereQuery('user.name', 'Taylor');
        $assert->whereQuery('user.id', '1');
    }

    public function testAssertWhereNestedQueryFailsWhenDoesNotMatchValue()
    {
        $assert = new AssertableUri('https://foo.com?user[name]=Taylor&user[id]=1');

        $this->expectAssertionException('Query [user.name] does not match the expected value.');

        $assert->whereQuery('user.name', 'baz');
    }

    public function testAssertWhereQueryUsingClosure()
    {
        $assert = new AssertableUri('https://foo.com?state='.str_repeat('a', 30));

        $assert->whereQuery('state', function ($state) {
            return strlen($state) === 30;
        });
    }

    public function testAssertQueryIsUrlDecoded()
    {
        $assert = new AssertableUri('foo.bar?redirect_uri=https%3A%2F%2Flaravel.com%2Ftest');

        $assert->whereQuery('redirect_uri', 'https://laravel.com/test');
    }

    public function testAssertWhereFragment()
    {
        $this->assert->whereFragment('anchor');
    }

    public function testAssertWhereFragmentFailsWhenDoesNotMatchValue()
    {
        $this->expectAssertionException('URI component [Fragment] does not match the expected value.');

        $this->assert->whereFragment('incorrect');
    }

    public function testAssertWhereFailsWhenComponentMissing()
    {
        $assert = new AssertableUri('https://foo.bar');

        $this->expectAssertionException('URI component [Path] does not exist.');

        $assert->wherePath('missing');
    }

    public function testCanChainMultipleAssertion()
    {
        $this->assert->whereQuery('foo', 'bar')->whereFragment('anchor');
    }

    public function testMustAssertAllQueriesWhenInteractedFlagIsSet()
    {
        $assert = new AssertableUri('https://foo.bar?name=Taylor&id=1');

        $assert->whereQuery('name', 'Taylor')
            ->whereQuery('id', '1')
            ->interacted();
    }

    public function testInteractedFailsWhenOneQueryIsNotAsserted()
    {
        $assert = new AssertableUri('https://foo.bar?name=Taylor&id=1');

        $this->expectAssertionException('Unexpected query were found on URI');

        $assert->whereQuery('name', 'Taylor')->interacted();
    }

    public function testCanDisableInteractionCheck()
    {
        $assert = new AssertableUri('https://foo.bar?name=Taylor&id=1');

        $assert->whereQuery('name', 'Taylor')
            ->etc()
            ->interacted();
    }

    public function testThrowExceptionWhenMethodDoesNotExist()
    {
        $this->expectException(BadMethodCallException::class);

        $this->assert->hasInvalidMethod();
    }

    private function expectAssertionException($message)
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage($message);
    }
}
