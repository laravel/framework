<?php

namespace Illuminate\Tests\Cache;

use Illuminate\Cache\RedisStore;
use Illuminate\Contracts\Redis\Factory;
use Illuminate\Redis\Connections\PhpRedisConnection;
use JMac\Testing\Double;
use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use JMac\Testing\Matching\Argument;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

class CacheRedisStoreTest extends TestCase
{
    use VerifiesDoubles;

    public function testGetAndSetPrefix()
    {
        $redis = $this->getRedis();
        $this->assertSame('prefix:', $redis->getPrefix());
        $redis->setPrefix('foo');
        $this->assertSame('foo', $redis->getPrefix());
        $redis->setPrefix(null);
        $this->assertEmpty($redis->getPrefix());
    }

    #[RequiresPhpExtension('redis', '>= 6.1.0')]
    public function testFlushStaleTagsStopsScanningWhenTheCursorReturnsToItsStartingValue()
    {
        $connection = Double::for(CacheRedisStoreTestConnection::class);
        $client = Double::for(\Redis::class);

        $connection->allows('_prefix')->with('')->returns('');
        $connection->allows('client')->returns($client);
        $client->allows('getOption')->returns(\Redis::SCAN_NORETRY);
        $connection->allows('zremrangebyscore');

        // A second call means the loop did not recognise the cursor it started with.
        $connection->expects('scan')->times(1)->returns([null, ['prefix:tag:foo:entries']]);

        $redis = $this->getRedis();
        $redis->getRedis()->allows('connection')->with('default')->returns($connection);

        $redis->flushStaleTags();
    }

    #[RequiresPhpExtension('redis')]
    public function testFlushStaleTagsDoesNotDoublePrefixTheMatchPattern()
    {
        $connection = Double::for(CacheRedisStoreTestConnection::class);
        $client = Double::for(\Redis::class);

        $connection->allows('_prefix')->with('')->returns('app_');
        $connection->allows('client')->returns($client);
        $client->allows('getOption')->with(\Redis::OPT_SCAN)->returns(\Redis::SCAN_PREFIX);
        $connection->expects('scan')
            ->with(Argument::any(), ['match' => 'prefix:tag:*:entries', 'count' => 1000])
            ->returns([null, ['app_prefix:tag:foo:entries']]);
        $connection->expects('zremrangebyscore')->with('prefix:tag:foo:entries', 0, Argument::any());

        $redis = $this->getRedis();
        $redis->getRedis()->allows('connection')->with('default')->returns($connection);

        $redis->flushStaleTags();
    }

    protected function getRedis()
    {
        return new RedisStore(Double::for(Factory::class), 'prefix:');
    }
}

// PhpRedisConnection only exposes _prefix() through __call, which a double can't configure.
class CacheRedisStoreTestConnection extends PhpRedisConnection
{
    public function _prefix($value)
    {
        return $value;
    }
}
