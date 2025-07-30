<?php

namespace Tests\Database;

use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use PHPUnit\Framework\TestCase;
use Illuminate\Tests\Database\Fixtures\Models\User;

class DatabaseEloquentStrictRelationsTest extends TestCase
{
    public function testValidateRelationExistenceThrows()
    {
        $user = new User();
        $trait = new class($user) {
            use \Illuminate\Database\Eloquent\Concerns\QueriesRelationships;
            public $model;
            public function __construct($model) { $this->model = $model; }
            public function getModel() { return $this->model; }
            public function callValidateRelationExistence($relation) {
                return $this->validateRelationExistence($relation);
            }
        };

        $this->expectException(RelationNotFoundException::class);
        $trait->callValidateRelationExistence('invalid_relation');
    }

    public function testValidateRelationExistenceDoesNotThrow()
    {
        $user = new User();
        $trait = new class($user) {
            use \Illuminate\Database\Eloquent\Concerns\QueriesRelationships;
            public $model;
            public function __construct($model) { $this->model = $model; }
            public function getModel() { return $this->model; }
            public function callValidateRelationExistence($relation) {
                return $this->validateRelationExistence($relation);
            }
        };

        // Should not throw for an existing method
        $trait->callValidateRelationExistence('getAuthIdentifier');
        $this->assertTrue(true);
    }
}
