<?php

namespace Illuminate\Tests\Cache;

use Illuminate\Cache\RedisStore;
use Illuminate\Contracts\Redis\Factory;
use Illuminate\Redis\Connections\PhpRedisConnection;
use JMac\Testing\Double;
use JMac\Testing\Matching\Argument;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

class CacheRedisStoreTest extends TestCase
{
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
        $calls = 0;

        $connection = Double::for(PhpRedisConnection::class)->passthru();
        $connection->allows('command')->with('_prefix', [''])->returns('');
        $connection->allows('command')->with('zremrangebyscore', Argument::any());
        $connection->allows('scan')->resolves(function () use (&$calls) {
            $calls++;

            // A second call means the loop did not recognise the cursor it started with.
            return $calls > 1 ? false : [null, ['prefix:tag:foo:entries']];
        });

        $redis = $this->getRedis();
        $redis->getRedis()->allows('connection')->with('default')->returns($connection);

        $redis->flushStaleTags();

        $this->assertSame(1, $calls);
    }

    protected function getRedis()
    {
        return new RedisStore(Double::for(Factory::class), 'prefix:');
    }
}
