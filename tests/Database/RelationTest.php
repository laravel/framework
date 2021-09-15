<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Eloquent\Relations\Relation;
use PHPUnit\Framework\TestCase;

class RelationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Relation::morphMap([
            'test' => 'test_model',
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Relation::morphMap([], false);
    }

    public function testGetMorphedModel()
    {
        self::assertEquals('test_model', Relation::getMorphedModel('test'));
        self::assertEquals('test_model', Relation::getMorphedModel(new class
        {
            public function __toString()
            {
                return 'test';
            }
        }));
    }
}
