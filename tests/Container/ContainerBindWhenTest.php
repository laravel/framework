<?php

namespace Illuminate\Tests\Container;

use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\BindWhen;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\TestCase;

#[RequiresPhp('>= 8.5.0')]
class ContainerBindWhenTest extends TestCase
{
    protected function tearDown(): void
    {
        Container::setInstance(null);

        parent::tearDown();
    }

    public function testBindWhenBindsFirstConditionThatPasses(): void
    {
        $container = new Container;

        $instance = $container->make(BindWhenInterface::class);

        $this->assertInstanceOf(BindWhenTrueConcrete::class, $instance);
    }

    public function testBindWhenSingletonAttribute(): void
    {
        $container = new Container;

        $first = $container->make(BindWhenSingletonInterface::class);
        $second = $container->make(BindWhenSingletonInterface::class);

        $this->assertInstanceOf(BindWhenSingletonConcrete::class, $first);
        $this->assertSame($first, $second);
    }

    public function testBindWhenThrowsWhenNoConditionPasses(): void
    {
        $this->expectException(BindingResolutionException::class);

        $container = new Container;
        $container->make(BindWhenNoMatchInterface::class);
    }

    public function testBindWhenTakesPrecedenceOverBind(): void
    {
        $container = new Container;
        $container->resolveEnvironmentUsing(fn () => true);

        $instance = $container->make(BindWhenAndBindInterface::class);

        $this->assertInstanceOf(BindWhenWinsConcrete::class, $instance);
    }

    public function testBindWhenFallsThroughToBind(): void
    {
        $container = new Container;
        $container->resolveEnvironmentUsing(fn () => true);

        $instance = $container->make(BindWhenFallbackInterface::class);

        $this->assertInstanceOf(BindFallbackConcrete::class, $instance);
    }
}

#[BindWhen(BindWhenFalseConcrete::class, static function () {
    return false;
})]
#[BindWhen(BindWhenTrueConcrete::class, static function () {
    return true;
})]
interface BindWhenInterface
{
}

class BindWhenFalseConcrete implements BindWhenInterface
{
}

class BindWhenTrueConcrete implements BindWhenInterface
{
}

#[BindWhen(BindWhenSingletonConcrete::class, static function () {
    return true;
})]
#[Singleton]
interface BindWhenSingletonInterface
{
}

class BindWhenSingletonConcrete implements BindWhenSingletonInterface
{
}

#[BindWhen(BindWhenNoMatchConcrete::class, static function () {
    return false;
})]
interface BindWhenNoMatchInterface
{
}

class BindWhenNoMatchConcrete implements BindWhenNoMatchInterface
{
}

#[BindWhen(BindWhenWinsConcrete::class, static function () {
    return true;
})]
#[Bind(BindLosesConcrete::class)]
interface BindWhenAndBindInterface
{
}

class BindWhenWinsConcrete implements BindWhenAndBindInterface
{
}

class BindLosesConcrete implements BindWhenAndBindInterface
{
}

#[BindWhen(BindWhenSkippedConcrete::class, static function () {
    return false;
})]
#[Bind(BindFallbackConcrete::class)]
interface BindWhenFallbackInterface
{
}

class BindWhenSkippedConcrete implements BindWhenFallbackInterface
{
}

class BindFallbackConcrete implements BindWhenFallbackInterface
{
}
