<?php

namespace Illuminate\Tests\Container\Fixtures;

use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\BindWhen;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Contracts\Container\Container as ContainerContract;

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

#[BindWhen(BindWhenConditionalConcrete::class, static function (ContainerContract $container) {
    return $container->bound(BindWhenCondition::class);
})]
interface BindWhenConditionalInterface
{
}

class BindWhenCondition
{
}

class BindWhenConditionalConcrete implements BindWhenConditionalInterface
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

#[Bind(BindBeforeConcrete::class, environments: 'foobar')]
#[BindWhen(BindWhenAfterConcrete::class, static function () {
    return true;
})]
interface BindBeforeBindWhenInterface
{
}

class BindBeforeConcrete implements BindBeforeBindWhenInterface
{
}

class BindWhenAfterConcrete implements BindBeforeBindWhenInterface
{
}
