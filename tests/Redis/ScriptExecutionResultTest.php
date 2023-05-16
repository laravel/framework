<?php

namespace Illuminate\Tests\Redis;

use Illuminate\Contracts\Redis\LuaScriptExecuteException;
use Illuminate\Redis\Lua\ScriptExecutionResult;
use PHPUnit\Framework\TestCase;
use Predis\Response\Error as PredisError;
use Predis\Response\ServerException;

class ScriptExecutionResultTest extends TestCase
{
    public function testSuccessResult()
    {
        $result = 'Success';

        $instance = ScriptExecutionResult::success($result);

        $this->assertInstanceOf(ScriptExecutionResult::class, $instance);
        $this->assertSame($result, $instance->getResult());
        $this->assertNull($instance->getException());
        $this->assertFalse($instance->isError());
        $this->assertNull($instance->getErrorType());
        $this->assertFalse($instance->isNoScriptError());
    }

    public function testErrorWithLuaScriptExecuteException()
    {
        $exception = new LuaScriptExecuteException('ERR An error occurred');
        $instance = ScriptExecutionResult::error($exception);

        $this->assertTrue($instance->isError());
        $this->expectException(LuaScriptExecuteException::class);
        $instance->getResult();
        $this->assertInstanceOf(LuaScriptExecuteException::class, $instance->getException());
        $this->assertSame('ERR', $instance->getErrorType());
        $this->assertTrue($instance->isNoScriptError());
    }

    public function testErrorWithPredisError()
    {
        $predisError = new PredisError('ERR An error occurred');
        $instance = ScriptExecutionResult::error($predisError);

        $this->assertTrue($instance->isError());
        $this->expectException(LuaScriptExecuteException::class);
        $instance->getResult();
        $this->assertInstanceOf(LuaScriptExecuteException::class, $instance->getException());
        $this->assertSame('ERR', $instance->getErrorType());
        $this->assertTrue($instance->isNoScriptError());
    }

    public function testErrorWithServerException()
    {
        $serverException = new ServerException('ERR An error occurred');
        $instance = ScriptExecutionResult::error($serverException);

        $this->assertTrue($instance->isError());
        $this->expectException(LuaScriptExecuteException::class);
        $instance->getResult();
        $this->assertInstanceOf(LuaScriptExecuteException::class, $instance->getException());
        $this->assertSame('ERR', $instance->getErrorType());
        $this->assertFalse($instance->isNoScriptError());
    }

    public function testIsNoScriptError()
    {
        $predisError = new PredisError('NOSCRIPT No matching script');
        $serverException = new ServerException('NOSCRIPT No matching script');
        $luaScriptException = new LuaScriptExecuteException('NOSCRIPT No matching script');

        $predisInstance = ScriptExecutionResult::error($predisError);
        $serverExceptionInstance = ScriptExecutionResult::error($serverException);
        $luaScriptExceptionInstance = ScriptExecutionResult::error($luaScriptException);

        $this->assertTrue($predisInstance->isNoScriptError());
        $this->assertTrue($serverExceptionInstance->isNoScriptError());
        $this->assertTrue($luaScriptExceptionInstance->isNoScriptError());
    }
}
