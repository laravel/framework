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

    public function testItCanBeConstructedWithParameters()
    {
        $definition = new MiddlewareDefinition('App\Http\Middleware\Role', ['admin', 'editor']);

        $this->assertSame('App\Http\Middleware\Role', $definition->class);
        $this->assertSame(['admin', 'editor'], $definition->parameters);
    }

    public function testItThrowsExceptionForEmptyClass()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Middleware class cannot be empty.');

        new MiddlewareDefinition('');
    }

    public function testToStringWithNoParameters()
    {
        $definition = new MiddlewareDefinition('App\Http\Middleware\Auth');

        $this->assertSame('App\Http\Middleware\Auth', (string) $definition);
    }

    public function testToStringWithParameters()
    {
        $definition = new MiddlewareDefinition('App\Http\Middleware\Role', ['admin', 'editor']);

        $this->assertSame('App\Http\Middleware\Role:admin,editor', (string) $definition);
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
