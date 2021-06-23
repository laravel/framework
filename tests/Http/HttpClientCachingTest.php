<?php


namespace Illuminate\Tests\Http;


use BadMethodCallException;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Http\Client\CacheHandlers\CacheHandler;
use Illuminate\Http\Client\CacheOptions;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use PHPUnit\Framework\TestCase;
use Mockery as m;

class HttpClientCachingTest extends TestCase
{
    /**
     * @var Factory
     */
    protected $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new Factory;
    }

    public function testThatTheHttpClientCanConstructCacheObject()
    {
        $this->factory->fake(['*' => ['result' => 'hello world']]);

        $response = $this->factory
            ->cache()->for(60 * 60)->by('foobar')
            ->get('http://foo.com/api');

        $this->assertSame('hello world', $response->json()['result']);

        $this->factory->assertSent(function(Request $request) {
            return $request->cacheOptions()->getKey() == 'foobar'
                && $request->cacheOptions()->getExpiry() == 60 * 60;
        });
    }

    public function testTheCacheExpiryCanBeSetUsingDateInstances()
    {
        Carbon::setTestNow(Carbon::now());

        $date = Carbon::now()->addDay();
        $cacheOptions = (new CacheOptions())->until($date);

        $this->assertEquals($date->diffInSeconds(), $cacheOptions->getExpiry());
    }

    public function testTheRequestHeaderCacheHandlerSetsTheCacheExpiryBasedOnMaxAge()
    {
        $cache = m::mock(Repository::class);
        $cache->shouldReceive('get', 'has')->andReturn(null);
        $cache->shouldReceive('put')
            ->once()
            ->withArgs(function($key, $value, $ttl) {
                return $ttl == 120;
            })
            ->andReturnTrue();

        $factory = new Factory(null, new CacheHandler($cache));

        $factory->fake(['*' => $this->factory->response([], 200, ['Cache-Control' => 'max-age=120'])]);

        $factory->cache()->get('http://foo.com/api');

        m::close();
    }

    public function testTheSMaxAgeHeaderOverridesTheMaxAgeHeader()
    {
        $cache = m::mock(Repository::class);
        $cache->shouldReceive('get', 'has')->andReturn(null);
        $cache->shouldReceive('put')
            ->once()
            ->withArgs(function($key, $value, $ttl) {
                return $ttl == 160;
            })
            ->andReturnTrue();

        $factory = new Factory(null, new CacheHandler($cache));

        $factory->fake(['*' => $this->factory->response([], 200, ['Cache-Control' => 'max-age=120,s-maxage=160'])]);

        $factory->cache()->get('http://foo.com/api');

        m::close();
    }

    public function testTheManualCacheTimeIsUsedOverAnyHeaderValue()
    {
        $cache = m::mock(Repository::class);
        $cache->shouldReceive('get', 'has')->andReturn(null);
        $cache->shouldReceive('put')
            ->once()
            ->withArgs(function($key, $value, $ttl) {
                return $ttl == 600;
            })
            ->andReturnTrue();

        $factory = new Factory(null, new CacheHandler($cache));

        $factory->fake(['*' => $this->factory->response([], 200, ['Cache-Control' => 'max-age=120, s-maxage=160'])]);

        $factory->cache()->for(600)->get('http://foo.com/api');

        m::close();
    }

    public function testIfNoExpiryTimeIsDiscoverableItWontCacheTheResponse()
    {
        $cache = m::mock(Repository::class);
        $cache->shouldReceive('get', 'has')->andReturn(null);
        $cache->shouldNotReceive('put');

        $factory = new Factory(null, new CacheHandler($cache));

        $factory->fake(['*' => $this->factory->response([], 200, [])]);

        $factory->cache()->get('http://foo.com/api');

        m::close();
    }

    public function testIfTheExpiryIsZeroItIsNotCached()
    {
        $cache = m::mock(Repository::class);
        $cache->shouldReceive('get', 'has')->andReturn(null);
        $cache->shouldNotReceive('put');

        $factory = new Factory(null, new CacheHandler($cache));

        $factory->fake(['*' => $this->factory->response([], 200, ['Cache-Control' => 'max-age=0'])]);

        $factory->cache()->get('http://foo.com/api'); // Uses max-age header
        $factory->cache()->for(0)->get('http://foo.com/api'); // Uses manual setting

        m::close();
    }

    public function testIfTheNoStoreHeaderIsOnTheResponseItWontBeCached()
    {
        $cache = m::mock(Repository::class);
        $cache->shouldReceive('get', 'has')->andReturn(null);
        $cache->shouldNotReceive('put');

        $factory = new Factory(null, new CacheHandler($cache));

        $factory->fake(['*' => $this->factory->response([], 200, ['Cache-Control' => 'no-store'])]);

        $factory->cache()->for(60)->get('http://foo.com/api');

        m::close();
    }

    public function testTheCacheJustUsesTheUrlAsTheUniqueKeyIfNoKeyIsSpecified()
    {
        $cache = m::mock(Repository::class);
        $cache->shouldReceive('get', 'has')->andReturn(null);
        $cache->shouldReceive('put')
            ->once()
            ->withArgs(function($key, $value, $ttl) {
                return $key == CacheHandler::CACHE_PREFIX . 'http://foo.com/api';
            })
            ->andReturnTrue();

        $factory = new Factory(null, new CacheHandler($cache));

        $factory->fake(['*' => $this->factory->response([], 200, ['Cache-Control' => 'max-age=120'])]);

        $factory->cache()->get('http://foo.com/api');

        m::close();
    }

    public function testTheCacheAppendsTheKeyIfSpecifiedToTheUrl()
    {
        $cache = m::mock(Repository::class);
        $cache->shouldReceive('get', 'has')->andReturn(null);
        $cache->shouldReceive('put')
            ->once()
            ->withArgs(function($key, $value, $ttl) {
                return $key == CacheHandler::CACHE_PREFIX . 'http://foo.com/api::123';
            })
            ->andReturnTrue();

        $factory = new Factory(null, new CacheHandler($cache));

        $factory->fake(['*' => $this->factory->response([], 200, ['Cache-Control' => 'max-age=120'])]);

        $factory->cache()->by('123')->get('http://foo.com/api');

        m::close();
    }

    public function testTheExpiresHeaderIsUsedIfNoCacheControlMaxAgeIsAvailable()
    {
        Carbon::setTestNow(now());

        $cache = m::mock(Repository::class);
        $cache->shouldReceive('get', 'has')->andReturn(null);
        $cache->shouldReceive('put')
            ->once()
            ->withArgs(function($key, $value, $ttl) {
                return $ttl == 120;
            })
            ->andReturnTrue();

        $factory = new Factory(null, new CacheHandler($cache));

        $factory->fake(['*' => $this->factory->response([], 200, ['Expires' => Carbon::now()->addSeconds(120)->format('D, d M Y H:i:s T')])]);

        $factory->cache()->get('http://foo.com/api');

        m::close();
    }

    public function testTheExpiresHeaderIsNotUsedIfMaxAgeIsPresent()
    {
        Carbon::setTestNow(now());

        $cache = m::mock(Repository::class);
        $cache->shouldReceive('get', 'has')->andReturn(null);
        $cache->shouldReceive('put')
            ->once()
            ->withArgs(function($key, $value, $ttl) {
                return $ttl == 10;
            })
            ->andReturnTrue();

        $factory = new Factory(null, new CacheHandler($cache));

        $factory->fake(['*' => $this->factory->response([], 200, [
            'Expires' => Carbon::now()->addSeconds(120)->format('D, d M Y H:i:s T'),
            'Cache-Control' => 'max-age=10'
        ])]);

        $factory->cache()->get('http://foo.com/api');

        m::close();
    }

    public function testACachedVersionOfTheResponseIsRetrievedWhenACacheableResponseIsRequestedAgain()
    {
        $cache = m::mock(Repository::class);
        $cache->shouldReceive('put')->once()->andReturnTrue();
        $cache->shouldReceive('has')->times(4)->andReturn(false, false, true, true);
        $cache->shouldReceive('get')
            ->once()
            ->with(CacheHandler::CACHE_PREFIX . 'http://foo.com/api')
            ->andReturn($this->factory->response(['response' => 'foo']));

        $factory = new Factory(null, new CacheHandler($cache));

        $factory->fake(['*' => $factory->response(['response' => 'bar'])]);

        $factory->cache()->for(60)->get('http://foo.com/api');
        $response = $factory->cache()->get('http://foo.com/api');

        $this->assertSame('{"response":"foo"}', $response->body());

        m::close();
    }

    public function testAnItemIsNotReCachedIfItAlreadyExists()
    {
        $cache = m::mock(Repository::class);
        $cache->shouldReceive('has')->twice()->andReturn(false, true);
        $cache->shouldNotReceive('put');

        $factory = new Factory(null, new CacheHandler($cache));

        $factory->fake(['*' => $factory->response(['response' => 'bar'])]);

        $factory->cache()->for(60)->get('http://foo.com/api');

        m::close();
    }

    public function testAnExceptionIsThrownIfAKeyIsNotSetOnAPrivateCache()
    {
        $this->expectException(BadMethodCallException::class);

        $factory = new Factory(null, new CacheHandler(m::mock(Repository::class)));
        $factory->fake(['*' => $factory->response(null, 200, ['Cache-Control' => 'private, max-age=3600'])]);

        $factory->cache()->get('http://foo.com/api');

        m::close();
    }

    public function testIfAKeyIsProvidedForAPrivateResponseTheResponseIsSuccessfullyCached()
    {
        $cache = m::mock(Repository::class);
        $cache->shouldReceive('has')->twice()->andReturn(false, true);
        $cache->shouldNotReceive('put');

        $factory = new Factory(null, new CacheHandler($cache));
        $factory->fake(['*' => $factory->response(null, 200, ['Cache-Control' => 'private, max-age=3600'])]);

        $factory->cache()->by('foobar')->get('http://foo.com/api');
    }

}
