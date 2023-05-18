<?php

namespace Illuminate\Tests\Redis;

use Illuminate\Contracts\Redis\LuaScriptExecuteException;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Redis\Lua\Executors\PhpRedisExecutor;
use Illuminate\Redis\Lua\Executors\PredisExecutor;
use Illuminate\Redis\Lua\LuaScript;
use Illuminate\Redis\Lua\LuaScriptArguments;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Predis\Response\Error;
use Predis\Response\ServerException;

class LuaExecutorTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    private function executeScriptTest($executor, $script, $expectedResult, $expectedErrorType = null, $expectedErrorMessage = null)
    {
        $connection = m::mock(Connection::class);
        $executor = new $executor($connection);

        if ($executor instanceof PhpRedisExecutor) {
            $redis = m::mock(\Redis::class);
            $connection->shouldReceive('client')->andReturn($redis);
            if ($expectedErrorType !== null) {
                $redis->shouldReceive('clearLastError')->andReturnTrue();
                $redis->shouldReceive('getLastError')->once()->andReturn($expectedErrorMessage);
            } else {
                $redis->shouldReceive('clearLastError')->once()->andReturnTrue();
            }
        }

        $expectation = $connection->shouldReceive('eval')->once()->with($script, 1, 'key1');
        if ($expectedResult instanceof \Throwable) {
            $expectation->andThrow($expectedResult);
        } else {
            $expectation->andReturn($expectedResult);
        }

        $result = $executor->execute(LuaScript::fromPlainScript($script), LuaScriptArguments::withKeys('key1'), false);

        if ($expectedErrorType !== null) {
            self::assertTrue($result->isError());
            self::assertInstanceOf(LuaScriptExecuteException::class, $result->getException());
            self::assertSame($expectedErrorType, $result->getErrorType());
            self::assertSame($expectedErrorMessage, $result->getException()->getMessage());
        } else {
            self::assertFalse($result->isError());
            self::assertSame($expectedResult, $result->getResult());
        }
    }

    public function testPhpRedisExecuteWithPlainScript()
    {
        // Test with success response
        $this->executeScriptTest(PhpRedisExecutor::class, 'return KEYS[1]', 'key1');

        // Test with error response
        $this->executeScriptTest(
            PhpRedisExecutor::class,
            'bad_syntax',
            false,
            'ERR',
            'ERR Error compiling script'
        );
    }

    public function testPredisExecuteWithPlainScript()
    {
        // Test with success response
        $this->executeScriptTest(PredisExecutor::class, 'return KEYS[1]', 'key1');

        // Test with error response
        $this->executeScriptTest(
            PredisExecutor::class,
            'bad_syntax',
            new Error('ERR Error compiling script'),
            'ERR',
            'ERR Error compiling script'
        );

        // Test with error response (exception raised)
        $this->executeScriptTest(
            PredisExecutor::class,
            'bad_syntax',
            new ServerException('ERR Error compiling script'),
            'ERR',
            'ERR Error compiling script'
        );
    }

    private function executePlainScriptWithCachingTest($executor, $predisThrow = false)
    {
        $connection = m::mock(Connection::class);
        $executor = new $executor($connection);
        $script = 'return KEYS[1]';

        if ($executor instanceof PredisExecutor) {
            $expectation = $connection->shouldReceive('command')->once()->with('evalsha', [sha1($script), ['key1'], 1]);
            if ($predisThrow) {
                $expectation->andThrow(new ServerException('NOSCRIPT NOSCRIPT No matching script'));
            } else {
                $expectation->andReturn(new Error('NOSCRIPT NOSCRIPT No matching script'));
            }
        } else {
            $redis = m::mock(\Redis::class);
            $redis->shouldReceive('clearLastError')->andReturnTrue();
            $redis->shouldReceive('getLastError')->andReturn('NOSCRIPT NOSCRIPT No matching script');
            $connection->shouldReceive('client')->andReturn($redis);
            $connection->shouldReceive('command')->once()->with('evalsha', [sha1($script), ['key1'], 1])->andReturnFalse();
        }

        $connection->shouldReceive('script')->with('load', $script)->andReturn(sha1($script));
        $connection->shouldReceive('command')->once()->with('evalsha', [sha1($script), ['key1'], 1])->andReturn('key1');

        $result = $executor->execute(LuaScript::fromPlainScript($script), LuaScriptArguments::withKeys('key1'), true);
        self::assertFalse($result->isError());
        self::assertSame('key1', $result->getResult());
    }

    public function testPhpRedisExecuteWithPlainScriptWithCaching()
    {
        $this->executePlainScriptWithCachingTest(PhpRedisExecutor::class);
    }

    public function testPredisExecuteWithPlainScriptWithCaching()
    {
        $this->executePlainScriptWithCachingTest(PredisExecutor::class);

        $this->executePlainScriptWithCachingTest(PredisExecutor::class, true);
    }
}
