<?php

namespace Illuminate\Tests\Support\Fixtures;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class UnusedAttr
{
}

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class ParentOnlyAttr
{
}

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class StrAttr
{
    public function __construct(public string $string)
    {
    }
}

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class NumAttr
{
    public function __construct(public int $number)
    {
    }
}

#[StrAttr('lazy'), StrAttr('dog'), NumAttr(2), NumAttr(3), ParentOnlyAttr]
class ParentClass
{
}

#[StrAttr('quick'), StrAttr('brown'), StrAttr('fox'), NumAttr(7)]
class ChildClass extends ParentClass
{
}
