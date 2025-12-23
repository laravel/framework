<?php

namespace Illuminate\Tests\Routing;

use Illuminate\Routing\MiddlewareDefinition;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MiddlewareDefinitionTest extends TestCase
{
    public function testItCanBeConstructedWithClassOnly()
    {
        $definition = new MiddlewareDefinition('App\Http\Middleware\Auth');

        $this->assertSame('App\Http\Middleware\Auth', $definition->class);
        $this->assertSame([], $definition->parameters);
    }

    public function testItCanBeConstructedWithPositionalParameters()
    {
        $definition = new MiddlewareDefinition('App\Http\Middleware\Role', ['admin', 'editor']);

        $this->assertSame('App\Http\Middleware\Role', $definition->class);
        $this->assertSame(['admin', 'editor'], $definition->parameters);
    }

    public function testItCanBeConstructedWithNamedParameters()
    {
        $definition = new MiddlewareDefinition('App\Http\Middleware\Role', ['role' => 'admin', 'level' => '5']);

        $this->assertSame('App\Http\Middleware\Role', $definition->class);
        $this->assertSame(['role' => 'admin', 'level' => '5'], $definition->parameters);
    }

    public function testItThrowsExceptionForEmptyClass()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Middleware class cannot be empty.');

        new MiddlewareDefinition('');
    }

    public function testHasNamedParametersReturnsFalseForEmptyParameters()
    {
        $definition = new MiddlewareDefinition('App\Http\Middleware\Auth');

        $this->assertFalse($definition->hasNamedParameters());
    }

    public function testHasNamedParametersReturnsFalseForPositionalParameters()
    {
        $definition = new MiddlewareDefinition('App\Http\Middleware\Role', ['admin', 'editor']);

        $this->assertFalse($definition->hasNamedParameters());
    }

    public function testHasNamedParametersReturnsTrueForNamedParameters()
    {
        $definition = new MiddlewareDefinition('App\Http\Middleware\Role', ['role' => 'admin']);

        $this->assertTrue($definition->hasNamedParameters());
    }

    public function testToStringWithNoParameters()
    {
        $definition = new MiddlewareDefinition('App\Http\Middleware\Auth');

        $this->assertSame('App\Http\Middleware\Auth', (string) $definition);
    }

    public function testToStringWithPositionalParameters()
    {
        $definition = new MiddlewareDefinition('App\Http\Middleware\Role', ['admin', 'editor']);

        $this->assertSame('App\Http\Middleware\Role:admin,editor', (string) $definition);
    }

    public function testToStringWithNamedParametersFlattensToPositional()
    {
        $definition = new MiddlewareDefinition('App\Http\Middleware\Role', ['role' => 'admin', 'level' => '5']);

        $this->assertSame('App\Http\Middleware\Role:admin,5', (string) $definition);
    }

    public function testToStringCastsNonStringParametersToString()
    {
        $definition = new MiddlewareDefinition('App\Http\Middleware\Throttle', [60, 1]);

        $this->assertSame('App\Http\Middleware\Throttle:60,1', (string) $definition);
    }

    public function testToStringWithSingleParameter()
    {
        $definition = new MiddlewareDefinition('App\Http\Middleware\Role', ['admin']);

        $this->assertSame('App\Http\Middleware\Role:admin', (string) $definition);
    }
}
