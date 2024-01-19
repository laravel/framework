<?php

namespace Illuminate\Tests\Support;

use Countable;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Tappable;
use Illuminate\Support\Traits\Transformable;
use PHPUnit\Framework\TestCase;

class SupportTransformableTest extends TestCase
{
    public function testTransformableClassWithCallback()
    {
        $name = TransformableClass::make()->tap(function ($transformable) {
            $transformable->setName('MyName');
        })->transform(function ($transformable) {
            return Str::of($transformable->getName())->snake()->toString();
        });

        $this->assertSame('my_name', $name);
    }

    public function testTransformableClassWithInvokableClass()
    {
        $name = TransformableClass::make()->tap(new class
        {
            public function __invoke($transformable)
            {
                $transformable->setName('MyName');
            }
        })->transform(new class
        {
            public function __invoke($transformable)
            {
                return Str::of($transformable->getName())->snake()->toString();
            }
        });

        $this->assertSame('my_name', $name);
    }

    public function testTransformableClassWithNoneInvokableClass()
    {
        $this->expectException('Error');

        $name = TransformableClass::make()->tap(function ($transformable) {
            $transformable->setName('MyName');
        })->transform(new class
        {
            public function getSnakeName($transformable)
            {
                return Str::of($transformable->getName())->snake()->toString();
            }
        });

        $this->assertSame('my_name', $name);
    }

    public function testTransformableClassWithCallbackAndEmptyClass()
    {
        $name = EmptyClass::make()->transform(function ($transformable) {
            return Str::of($transformable->getName())->snake()->toString();
        }, 'OtherName');

        $this->assertSame('OtherName', $name);
    }

    public function testTransformableClassWithInvokableClassAndEmptyClass()
    {
        $name = EmptyClass::make()->transform(new class
        {
            public function __invoke($transformable)
            {
                return Str::of($transformable->getName())->snake()->toString();
            }
        }, 'OtherName');

        $this->assertSame('OtherName', $name);
    }

    public function testTransformableClassWithNoneInvokableClassEmptyClass()
    {
        $this->expectException('Error');

        $name = EmptyClass::make()->transform(new class
        {
            public function getSnakeName($transformable)
            {
                return Str::of($transformable->getName())->snake()->toString();
            }
        }, 'OtherName');

        $this->assertSame('OtherName', $name);
    }

    public function testTransformableClassWithCallbackAndEmptyClassAndCallbackDefault()
    {
        $name = EmptyClass::make()->transform(function ($transformable) {
            return Str::of($transformable->getName())->snake()->toString();
        }, function($transformable) {
            return 'OtherName';
        });

        $this->assertSame('OtherName', $name);
    }

    public function testTransformableClassWithInvokableClassAndEmptyClassAndCallbackDefault()
    {
        $name = EmptyClass::make()->transform(new class
        {
            public function __invoke($transformable)
            {
                return Str::of($transformable->getName())->snake()->toString();
            }
        }, function($transformable) {
            return 'OtherName';
        });

        $this->assertSame('OtherName', $name);
    }

    public function testTransformableClassWithNoneInvokableClassEmptyClassAndCallbackDefault()
    {
        $this->expectException('Error');

        $name = EmptyClass::make()->transform(new class
        {
            public function getSnakeName($transformable)
            {
                return Str::of($transformable->getName())->snake()->toString();
            }
        }, function($transformable) {
            return 'OtherName';
        });

        $this->assertSame('OtherName', $name);
    }
}

class TransformableClass
{
    use Tappable;
    use Transformable;

    private $name;

    public static function make()
    {
        return new static;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }
}

class EmptyClass implements Countable
{
    use Transformable;

    public static function make()
    {
        return new static;
    }

    public function count()
    {
        return 0;
    }
}
