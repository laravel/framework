<?php

namespace Illuminate\Tests\Support;

use Attribute;
use Illuminate\Support\Traits\ReadsClassAttributes;
use LogicException;
use PHPUnit\Framework\TestCase;

class SupportReadsClassAttributesTest extends TestCase
{
    public function test_trait_attribute_is_resolved()
    {
        $reader = new class
        {
            use ReadsClassAttributes;

            public function read($target)
            {
                return $this->getAttributeValue($target, ReadsClassAttributesTestAttr::class, 'value');
            }
        };

        $this->assertSame('from_trait', $reader->read(new ReadsClassAttributesTestWithTrait));
    }

    public function test_identical_trait_attributes_are_allowed()
    {
        $reader = new class
        {
            use ReadsClassAttributes;

            public function read($target)
            {
                return $this->getAttributeValue($target, ReadsClassAttributesTestAttr::class, 'value');
            }
        };

        $this->assertSame('same', $reader->read(new ReadsClassAttributesTestWithIdenticalTraits));
    }

    public function test_conflicting_trait_attributes_throw_logic_exception()
    {
        $reader = new class
        {
            use ReadsClassAttributes;

            public function read($target)
            {
                return $this->getAttributeValue($target, ReadsClassAttributesTestAttr::class, 'value');
            }
        };

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('declare conflicting values');

        $reader->read(new ReadsClassAttributesTestWithConflictingTraits);
    }

    public function test_class_attribute_takes_precedence_over_conflicting_traits()
    {
        $reader = new class
        {
            use ReadsClassAttributes;

            public function read($target)
            {
                return $this->getAttributeValue($target, ReadsClassAttributesTestAttr::class, 'value');
            }
        };

        $this->assertSame('from_class', $reader->read(new ReadsClassAttributesTestOverridingConflictingTraits));
    }
}

#[Attribute(Attribute::TARGET_CLASS)]
class ReadsClassAttributesTestAttr
{
    public function __construct(public string $value)
    {
    }
}

#[ReadsClassAttributesTestAttr('from_alpha')]
trait ReadsClassAttributesTestAlphaTrait
{
}

#[ReadsClassAttributesTestAttr('from_beta')]
trait ReadsClassAttributesTestBetaTrait
{
}

#[ReadsClassAttributesTestAttr('same')]
trait ReadsClassAttributesTestSameTraitA
{
}

#[ReadsClassAttributesTestAttr('same')]
trait ReadsClassAttributesTestSameTraitB
{
}

#[ReadsClassAttributesTestAttr('from_trait')]
trait ReadsClassAttributesTestSingleTrait
{
}

class ReadsClassAttributesTestWithTrait
{
    use ReadsClassAttributesTestSingleTrait;
}

class ReadsClassAttributesTestWithIdenticalTraits
{
    use ReadsClassAttributesTestSameTraitA, ReadsClassAttributesTestSameTraitB;
}

class ReadsClassAttributesTestWithConflictingTraits
{
    use ReadsClassAttributesTestAlphaTrait, ReadsClassAttributesTestBetaTrait;
}

#[ReadsClassAttributesTestAttr('from_class')]
class ReadsClassAttributesTestOverridingConflictingTraits
{
    use ReadsClassAttributesTestAlphaTrait, ReadsClassAttributesTestBetaTrait;
}
