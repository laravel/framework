<?php

namespace Illuminate\Tests\Redis;

use Illuminate\Redis\Lua\LuaScript;
use Illuminate\Redis\Lua\LuaScriptArguments;
use PHPUnit\Framework\TestCase;

class LuaScriptTest extends TestCase
{
    public function testInstanceWithPlainScript()
    {
        $script = 'return 1 + 1;';

        $instance = LuaScript::fromPlainScript($script);

        $this->assertInstanceOf(LuaScript::class, $instance);
        $this->assertSame($script, $instance->getScript());
        $this->assertNull($instance->getSha1());
        $this->assertTrue($instance->isPlainScript());
    }

    public function testInstanceWithSHA1Hash()
    {
        $sha1 = 'some_sha1_hash';

        $instance = LuaScript::fromSHA1Hash($sha1);

        $this->assertInstanceOf(LuaScript::class, $instance);
        $this->assertNull($instance->getScript());
        $this->assertSame($sha1, $instance->getSha1());
        $this->assertFalse($instance->isPlainScript());
    }

    public function testEmptyInstance()
    {
        $instance = LuaScriptArguments::empty();

        $this->assertInstanceOf(LuaScriptArguments::class, $instance);
        $this->assertSame([], $instance->getKeys());
        $this->assertSame([], $instance->getArguments());
        $this->assertSame(0, $instance->getNumberOfKeys());
        $this->assertSame([], $instance->toArray());
    }

    public function testInstanceWithKeysAndArguments()
    {
        $keys = ['key1', 'key2'];
        $arguments = ['arg1', 'arg2'];

        $instance = LuaScriptArguments::with($keys, $arguments);

        $this->assertInstanceOf(LuaScriptArguments::class, $instance);
        $this->assertSame($keys, $instance->getKeys());
        $this->assertSame($arguments, $instance->getArguments());
        $this->assertSame(2, $instance->getNumberOfKeys());
        $this->assertSame(['key1', 'key2', 'arg1', 'arg2'], $instance->toArray());
    }

    public function testInstanceWithKeys()
    {
        $keys = ['key1', 'key2'];

        $instance = LuaScriptArguments::withKeys(...$keys);

        $this->assertInstanceOf(LuaScriptArguments::class, $instance);
        $this->assertSame([], $instance->getArguments());
        $this->assertSame($keys, $instance->getKeys());
        $this->assertSame(2, $instance->getNumberOfKeys());
        $this->assertSame($keys, $instance->toArray());
    }

    public function testInstanceWithArguments()
    {
        $arguments = ['arg1', 'arg2'];

        $instance = LuaScriptArguments::withArguments(...$arguments);

        $this->assertInstanceOf(LuaScriptArguments::class, $instance);
        $this->assertSame($arguments, $instance->getArguments());
        $this->assertSame([], $instance->getKeys());
        $this->assertSame(0, $instance->getNumberOfKeys());
        $this->assertSame($arguments, $instance->toArray());
    }
}
