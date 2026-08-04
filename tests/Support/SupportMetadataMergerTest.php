<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\MetadataMerger;
use PHPUnit\Framework\TestCase;

class SupportMetadataMergerTest extends TestCase
{
    public function testItMergesAssociativeArraysRecursively()
    {
        $this->assertSame([
            'operations' => [
                'owner' => 'finance',
                'monitoring' => [
                    'enabled' => true,
                    'channel' => 'finance-alerts',
                ],
            ],
        ], MetadataMerger::merge(
            [
                'operations' => [
                    'owner' => 'platform',
                    'monitoring' => [
                        'enabled' => true,
                        'channel' => 'operations',
                    ],
                ],
            ],
            [
                'operations' => [
                    'owner' => 'finance',
                    'monitoring' => [
                        'channel' => 'finance-alerts',
                    ],
                ],
            ],
        ));
    }

    public function testItReplacesListValues()
    {
        $this->assertSame([
            'roles' => ['finance'],
        ], MetadataMerger::merge(
            ['roles' => ['operator', 'administrator']],
            ['roles' => ['finance']],
        ));
    }

    public function testItReplacesScalarValues()
    {
        $this->assertSame([
            'enabled' => false,
        ], MetadataMerger::merge(
            ['enabled' => true],
            ['enabled' => false],
        ));
    }

    public function testItAllowsValueTypeChanges()
    {
        $this->assertSame([
            'monitoring' => false,
        ], MetadataMerger::merge(
            ['monitoring' => ['enabled' => true]],
            ['monitoring' => false],
        ));
    }

    public function testItReplacesAssociativeValuesWithEmptyLists()
    {
        $this->assertSame([
            'operations' => [],
        ], MetadataMerger::merge(
            ['operations' => ['owner' => 'finance']],
            ['operations' => []],
        ));
    }

    public function testItPreservesExistingMetadataForAnEmptyIncomingArray()
    {
        $this->assertSame(
            ['enabled' => true],
            MetadataMerger::merge(['enabled' => true], []),
        );
    }

    public function testItMergesIntoAnEmptyExistingArray()
    {
        $this->assertSame(
            ['enabled' => true],
            MetadataMerger::merge([], ['enabled' => true]),
        );
    }

    public function testItPreservesNumericRootKeyBehavior()
    {
        $this->assertSame(
            [4, 2, 3],
            MetadataMerger::merge([1, 2, 3], [4]),
        );
    }
}
