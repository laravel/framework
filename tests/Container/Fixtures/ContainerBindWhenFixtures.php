<?php

namespace Illuminate\Tests\Container;

use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\BindWhen;
use Illuminate\Container\Attributes\Singleton;

/*
 * These fixtures embed static closures inside attribute arguments, which is only
 * valid on PHP >= 8.5. They live in a standalone file that is required at runtime
 * by the PHP >= 8.5 gated tests so that older PHP versions never compile them.
 */

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
