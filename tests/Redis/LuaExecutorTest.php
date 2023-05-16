<?php

namespace Illuminate\Tests\Redis;

use Illuminate\Contracts\Redis\LuaScriptExecuteException;
use Illuminate\Foundation\Testing\Concerns\InteractsWithRedis;
use Illuminate\Redis\Lua\Executors\PhpRedisExecutor;
use Illuminate\Redis\Lua\Executors\PredisExecutor;
use Illuminate\Redis\Lua\LuaScript;
use Illuminate\Redis\Lua\LuaScriptArguments;
use PHPUnit\Framework\TestCase;

class LuaExecutorTest extends TestCase
{
    use InteractsWithRedis;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpRedis();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->tearDownRedis();
    }

    /**
     * @return \Illuminate\Redis\Lua\LuaScriptExecutor[]
     */
    private function getExecutors()
    {
        return [
            new PhpRedisExecutor($this->redis['phpredis']->connection()),
            new PredisExecutor($this->redis['predis']->connection())
        ];
    }

    public function testExecuteWithPlainScript()
    {
        foreach ($this->getExecutors() as $executor) {
            $result = $executor->execute(LuaScript::fromPlainScript('return "OK"'), LuaScriptArguments::empty());

            self::assertFalse($result->isError());
            self::assertSame("OK", $result->getResult());
        }
    }

    public function testExecuteWithPlainScriptWithError()
    {
        foreach ($this->getExecutors() as $executor) {
            $result = $executor->execute(LuaScript::fromPlainScript("bad_syntax"), LuaScriptArguments::empty());

            $this->assertTrue($result->isError());
            $this->assertSame("ERR", $result->getErrorType());
            $this->assertFalse($result->isNoScriptError());

            $this->expectException(LuaScriptExecuteException::class);
            $result->getResult();
        }
    }

    public function testExecuteWithPlainScriptCaching()
    {
        foreach ($this->getExecutors() as $executor) {
            $result = $executor->execute(LuaScript::fromPlainScript('return "OK"'), LuaScriptArguments::empty(), true);

            self::assertFalse($result->isError());
            self::assertSame("OK", $result->getResult());
        }
    }

    public function testExecuteWithPlainScriptCachingWithError()
    {
        foreach ($this->getExecutors() as $executor) {
            // Should rise exception in when try to load the script into redis

            $this->expectException(LuaScriptExecuteException::class);
            $executor->execute(LuaScript::fromPlainScript("bad_syntax"), LuaScriptArguments::empty(), true);
        }
    }

    public function testExecuteWithHashWhenScriptNotLoaded()
    {
        foreach ($this->getExecutors() as $executor) {
            $result = $executor->execute(LuaScript::fromSHA1Hash("some_sha1_hash"), LuaScriptArguments::empty());

            $this->assertTrue($result->isError());
            $this->assertTrue($result->isNoScriptError());

            $this->expectException(LuaScriptExecuteException::class);
            $result->getResult();
        }
    }

    public function testExecuteWithHashWhenScriptLoaded()
    {
        foreach ($this->getExecutors() as $executor) {
            $sha1 = $executor->loadScript('return "OK"');

            $result = $executor->execute(LuaScript::fromSHA1Hash($sha1), LuaScriptArguments::empty());

            $this->assertFalse($result->isError());
            $this->assertSame('OK', $result->getResult());
        }
    }
}
